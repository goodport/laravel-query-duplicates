<?php

namespace Goodport\QueryDuplicates;

use Goodport\QueryDuplicates\Aggregator\DuplicateQueryAggregator;
use Goodport\QueryDuplicates\Config\DuplicateQuerySettings;
use Goodport\QueryDuplicates\Http\TrackDuplicateQueries;
use Goodport\QueryDuplicates\Listener\RecordQuery;
use Goodport\QueryDuplicates\Logging\DuplicateQueryWriter;
use Goodport\QueryDuplicates\Logging\LogChannelResolver;
use Goodport\QueryDuplicates\Logging\ReportDeduplicator;
use Goodport\QueryDuplicates\Support\ExcludeMatcher;
use Goodport\QueryDuplicates\Support\StackRelationQueryDetector;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel as KernelContract;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class QueryDuplicatesServiceProvider extends ServiceProvider
{
    private bool $trackingRegistered = false;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/query-duplicates.php', 'query-duplicates');

        $this->app->singleton(DuplicateQuerySettings::class, function (): DuplicateQuerySettings {
            return DuplicateQuerySettings::fromConfig($this->packageConfig());
        });

        $this->app->singleton(ExcludeMatcher::class, function (Application $app): ExcludeMatcher {
            $settings = $app->make(DuplicateQuerySettings::class);

            return new ExcludeMatcher($settings->exclude);
        });

        $this->app->singleton(DuplicateQueryAggregator::class, function (Application $app): DuplicateQueryAggregator {
            return new DuplicateQueryAggregator(
                settings: $app->make(DuplicateQuerySettings::class),
                excludeMatcher: $app->make(ExcludeMatcher::class),
                relationDetector: new StackRelationQueryDetector(__DIR__),
            );
        });

        $this->app->singleton(LogChannelResolver::class);

        $this->app->singleton(ReportDeduplicator::class, function (Application $app): ReportDeduplicator {
            return new ReportDeduplicator($app->make(CacheRepository::class));
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/query-duplicates.php' => config_path('query-duplicates.php'),
        ], 'query-duplicates-config');

        $config = $this->packageConfig();

        if (! DuplicateQuerySettings::enabledFromConfig($config['enabled'] ?? null)) {
            return;
        }

        if ($this->trackingRegistered) {
            return;
        }

        $settings = DuplicateQuerySettings::fromConfig($config);

        $this->app->instance(DuplicateQuerySettings::class, $settings);
        $this->app->instance(
            ExcludeMatcher::class,
            new ExcludeMatcher($settings->exclude),
        );

        $logger = $this->app->make(LogChannelResolver::class)->resolve($settings->logChannel);

        $this->app->instance(
            DuplicateQueryWriter::class,
            new DuplicateQueryWriter(
                logger: $logger,
                deduplicator: $this->app->make(ReportDeduplicator::class),
            ),
        );

        $this->app->make(Dispatcher::class)->listen(
            QueryExecuted::class,
            $this->app->make(RecordQuery::class),
        );

        /** @var HttpKernel $kernel */
        $kernel = $this->app->make(KernelContract::class);
        $kernel->prependMiddleware(TrackDuplicateQueries::class);

        $this->trackingRegistered = true;
    }

    /**
     * Returns the package configuration array.
     *
     * @return array<string, mixed>
     */
    private function packageConfig(): array
    {
        $config = $this->app['config']->get('query-duplicates');

        if (! is_array($config)) {
            throw new InvalidArgumentException('The [query-duplicates] config must be an array.');
        }

        return $config;
    }
}
