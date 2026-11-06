<?php

namespace Goodport\QueryDuplicates\Tests;

use Goodport\QueryDuplicates\QueryDuplicatesServiceProvider;
use Goodport\QueryDuplicates\Tests\Support\DefinesLogChannels;
use Goodport\QueryDuplicates\Tests\Support\DefinesQueryRoutes;
use Goodport\QueryDuplicates\Tests\Support\FakeQueryConnection;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel as KernelContract;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use DefinesLogChannels;
    use DefinesQueryRoutes;

    protected bool $packageEnabled = true;

    protected ?string $logChannel = 'query-duplicates';

    protected ?bool $strict = null;

    protected ?int $threshold = null;

    /**
     * @var list<string|array{sql: string, bindings?: array<int, mixed>}>|null
     */
    protected ?array $exclude = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make('db')->extend(
            'fake',
            static fn (array $config, string $name): FakeQueryConnection => new FakeQueryConnection($name),
        );

        $this->clearLogFiles($this->app);
    }

    protected function tearDown(): void
    {
        $this->clearLogFiles($this->app);

        parent::tearDown();
    }

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [QueryDuplicatesServiceProvider::class];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $this->defineLogChannels($app);

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'fake',
            'database' => 'testing',
        ]);
        $app['config']->set('database.connections.secondary', [
            'driver' => 'fake',
            'database' => 'secondary',
        ]);

        $app['config']->set('query-duplicates.enabled', $this->packageEnabled);
        $app['config']->set('query-duplicates.log_channel', $this->logChannel);

        $this->setOptionalPackageConfig($app);
    }

    /**
     * Returns the application HTTP kernel with Foundation-class methods.
     */
    protected function httpKernel(): HttpKernel
    {
        /** @var HttpKernel $kernel */
        $kernel = $this->app->make(KernelContract::class);

        return $kernel;
    }

    /**
     * Returns group reports in the order they appear.
     *
     * @return list<array<string, mixed>>
     */
    protected function loggedRecords(string $channel = 'query-duplicates'): array
    {
        return array_values(array_filter(
            $this->allLogRecords($channel),
            static fn (array $record): bool => isset($record['context']['query_count']),
        ));
    }

    /**
     * Returns all channel records, including service entries.
     *
     * @return list<array<string, mixed>>
     */
    protected function allLogRecords(string $channel = 'query-duplicates'): array
    {
        $path = $this->logFilePath($channel);

        if (! is_file($path)) {
            return [];
        }

        $records = [];

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $records[] = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        }

        return $records;
    }

    /**
     * Returns the raw log file contents.
     */
    protected function rawLogContents(string $channel = 'query-duplicates'): string
    {
        $path = $this->logFilePath($channel);

        return is_file($path) ? (string) file_get_contents($path) : '';
    }

    /**
     * Sets only the settings overridden by the test.
     */
    private function setOptionalPackageConfig(Application $app): void
    {
        $overrides = array_filter([
            'strict' => $this->strict,
            'threshold' => $this->threshold,
            'exclude' => $this->exclude,
        ], static fn (mixed $value): bool => $value !== null);

        foreach ($overrides as $key => $value) {
            $app['config']->set("query-duplicates.{$key}", $value);
        }
    }

    private function logFilePath(string $channel): string
    {
        return $this->app->storagePath('logs/'.$this->logFileName($channel));
    }
}
