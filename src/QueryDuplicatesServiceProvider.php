<?php

namespace Goodport\QueryDuplicates;

use Goodport\QueryDuplicates\Aggregator\DuplicateQueryAggregator;
use Goodport\QueryDuplicates\Config\AdminRouteDefaults;
use Goodport\QueryDuplicates\Config\DuplicateQuerySettings;
use Goodport\QueryDuplicates\Http\TrackDuplicateQueries;
use Goodport\QueryDuplicates\Listener\RecordQuery;
use Goodport\QueryDuplicates\Recording\QueryDuplicateRecorder;
use Goodport\QueryDuplicates\Storage\SqliteStorage;
use Goodport\QueryDuplicates\Support\ExcludeMatcher;
use Goodport\QueryDuplicates\Support\RouteExcludeMatcher;
use Goodport\QueryDuplicates\Support\StackRelationQueryDetector;
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

    private bool $routesRegistered = false;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/query-duplicates.php', 'query-duplicates');

        $this->app->singleton(DuplicateQuerySettings::class, function (): DuplicateQuerySettings {
            return DuplicateQuerySettings::fromConfig($this->packageConfig());
        });

        $this->app->singleton(ExcludeMatcher::class, function (Application $app): ExcludeMatcher {
            $settings = $app->make(DuplicateQuerySettings::class);

            return new ExcludeMatcher($settings->exclusions->queries);
        });

        $this->app->singleton(RouteExcludeMatcher::class, function (Application $app): RouteExcludeMatcher {
            $settings = $app->make(DuplicateQuerySettings::class);

            return new RouteExcludeMatcher($settings->exclusions->routes);
        });

        $this->app->singleton(DuplicateQueryAggregator::class, function (Application $app): DuplicateQueryAggregator {
            return new DuplicateQueryAggregator(
                settings: $app->make(DuplicateQuerySettings::class),
                excludeMatcher: $app->make(ExcludeMatcher::class),
                relationDetector: new StackRelationQueryDetector(__DIR__),
            );
        });

        $this->app->singleton(QueryDuplicateRecorder::class);
    }

    public function boot(): void
    {
        $this->registerStorageConnection();

        $this->publishes([
            __DIR__.'/../config/query-duplicates.php' => config_path('query-duplicates.php'),
        ], 'query-duplicates-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/query-duplicates'),
        ], 'query-duplicates-views');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'query-duplicates');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'query-duplicates');

        $this->normalizeAdminRoute();

        $config = $this->packageConfig();

        $this->registerAdminRoutes($config);

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
            new ExcludeMatcher($settings->exclusions->queries),
        );
        $this->app->instance(
            RouteExcludeMatcher::class,
            new RouteExcludeMatcher($settings->exclusions->routes),
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
     * Registers the dedicated SQLite connection owned by the package.
     *
     * The connection never replaces the application default. WAL and a busy
     * timeout reduce [database is locked] failures during concurrent tests.
     */
    private function registerStorageConnection(): void
    {
        $this->app['config']->set(
            'database.connections.'.SqliteStorage::CONNECTION,
            [
                'driver' => 'sqlite',
                'database' => (new SqliteStorage)->path(),
                'prefix' => '',
                'foreign_key_constraints' => false,
                'busy_timeout' => 5000,
                'journal_mode' => 'WAL',
            ],
        );
    }

    /**
     * Registers the admin routes when they are enabled in the configuration.
     *
     * @param  array<string, mixed>  $config
     */
    private function registerAdminRoutes(array $config): void
    {
        $adminRoute = AdminRouteDefaults::normalize($config['admin_route'] ?? []);

        if (! $adminRoute['enabled']) {
            return;
        }

        if ($this->routesRegistered) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        $this->routesRegistered = true;
    }

    /**
     * Restores safe defaults after Laravel's flat config merge replaces the
     * package [admin_route] array with a partial application override.
     *
     * The normalized array is written back before any route is registered, so
     * the gate middleware cannot be lost by a partial override.
     */
    private function normalizeAdminRoute(): void
    {
        $config = $this->packageConfig();
        $config['admin_route'] = AdminRouteDefaults::normalize($config['admin_route'] ?? []);

        $this->app['config']->set('query-duplicates', $config);
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
