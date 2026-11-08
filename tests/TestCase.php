<?php

namespace Goodport\QueryDuplicates\Tests;

use Goodport\QueryDuplicates\Aggregator\QueryGroup;
use Goodport\QueryDuplicates\Models\QueryDuplicate;
use Goodport\QueryDuplicates\QueryDuplicatesServiceProvider;
use Goodport\QueryDuplicates\Storage\SqliteStorage;
use Goodport\QueryDuplicates\Tests\Support\DefinesQueryRoutes;
use Goodport\QueryDuplicates\Tests\Support\FakeQueryConnection;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel as KernelContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use DefinesQueryRoutes;

    protected bool $packageEnabled = true;

    protected bool $adminRoutesEnabled = true;

    protected ?bool $strict = null;

    /**
     * @var array{get?: array{enabled?: bool, threshold?: int}, other?: array{enabled?: bool, threshold?: int}}|null
     */
    protected ?array $methods = null;

    /**
     * @var list<array{route?: string, query?: string|array{sql: string, bindings?: array<int, mixed>}}>|null
     */
    protected ?array $exclude = null;

    private int $duplicateSequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make('db')->extend(
            'fake',
            static fn (array $config, string $name): FakeQueryConnection => new FakeQueryConnection($name),
        );

        $this->app->make(SqliteStorage::class)->ensure();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

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
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));

        $app['config']->set('query-duplicates.storage_path', SqliteStorage::MEMORY_PATH);

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
        $app['config']->set('query-duplicates.admin_route.enabled', $this->adminRoutesEnabled);

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
     * Allows access to the package admin page.
     */
    protected function allowQueryDuplicates(): void
    {
        Gate::define('view-query-duplicates', static fn (?object $user = null): bool => true);
    }

    /**
     * Returns stored reports in insertion order.
     *
     * @return Collection<int, QueryDuplicate>
     */
    protected function duplicateRows(): Collection
    {
        return QueryDuplicate::query()->orderBy('id')->get();
    }

    /**
     * Returns the only stored report and asserts that it is the only one.
     */
    protected function singleDuplicate(): QueryDuplicate
    {
        $rows = $this->duplicateRows();

        $this->assertCount(1, $rows, 'Exactly one report row was expected.');

        return $rows->first();
    }

    /**
     * Asserts the number of stored reports.
     */
    protected function assertDuplicateCount(int $expected): void
    {
        $this->assertSame($expected, QueryDuplicate::query()->count());
    }

    /**
     * Inserts a report row with explicit attributes.
     *
     * @param  array<string, mixed>  $overrides
     */
    protected function insertDuplicate(array $overrides = []): QueryDuplicate
    {
        $reportedAt = now();
        $reportKey = $overrides['report_key'] ?? hash('sha256', 'report-'.++$this->duplicateSequence);

        QueryDuplicate::query()->insert(array_merge([
            'report_key' => $reportKey,
            'method' => 'GET',
            'path' => 'posts',
            'route' => 'posts.index',
            'sql' => 'select * from `posts`',
            'query_count' => 2,
            'time_ms' => 1.5,
            'type' => QueryGroup::TYPE__DUPLICATE,
            'occurrences' => 1,
            'created_at' => $reportedAt,
            'updated_at' => $reportedAt,
        ], $overrides));

        $row = QueryDuplicate::query()->where('report_key', $reportKey)->first();

        $this->assertInstanceOf(QueryDuplicate::class, $row);

        return $row;
    }

    /**
     * Sets only the settings overridden by the test.
     */
    private function setOptionalPackageConfig(Application $app): void
    {
        $overrides = array_filter([
            'strict' => $this->strict,
            'methods' => $this->methods,
            'exclude' => $this->exclude,
        ], static fn (mixed $value): bool => $value !== null);

        foreach ($overrides as $key => $value) {
            $app['config']->set("query-duplicates.{$key}", $value);
        }
    }
}
