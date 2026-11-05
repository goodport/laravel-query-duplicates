<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Aggregator\QueryGroup;
use Goodport\QueryDuplicates\Tests\TestCase;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Routing\Router;
use stdClass;

final class RelationDetectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make('db')->extend(
            'relation',
            static fn (array $config, string $name): RelationQueryConnection => new RelationQueryConnection($name),
        );
        $this->app['config']->set('database.default', 'relation');
        $this->app['config']->set('database.connections.relation', [
            'driver' => 'relation',
            'database' => 'relation',
        ]);
    }

    /**
     * @param  Router  $router
     */
    protected function defineRoutes($router): void
    {
        $router->get('/duplicates/relations', function () {
            $parents = RelationParent::query()->get();

            foreach ($parents as $parent) {
                $parent->children;
            }

            return response('ok');
        })->name('testing.duplicates.relations');
    }

    /**
     * A lazy relation loaded in a loop is an N+1 query.
     */
    public function test_lazy_relation_in_loop_is_n_plus_one(): void
    {
        $this->get(route('testing.duplicates.relations'))->assertOk();

        $records = $this->loggedRecords();

        $this->assertCount(1, $records);
        $this->assertSame(QueryGroup::TYPE__N_PLUS_ONE, $records[0]['context']['type']);
        $this->assertSame(3, $records[0]['context']['query_count']);
        $this->assertStringContainsString('relation_children', $records[0]['context']['sql']);
    }
}

final class RelationParent extends Model
{
    public $timestamps = false;

    /**
     * @return HasMany<RelationChild, $this>
     */
    public function children()
    {
        return $this->hasMany(RelationChild::class, 'relation_parent_id');
    }
}

final class RelationChild extends Model
{
    public $timestamps = false;
}

/**
 * Returns fixed relation rows without a real database.
 */
final class RelationQueryConnection extends Connection
{
    public function __construct(string $name)
    {
        parent::__construct(new stdClass, $name, '', ['name' => $name]);
    }

    /**
     * @param  string  $query
     * @param  array<int, mixed>  $bindings
     * @param  bool  $useReadPdo
     * @return array<int, array<string, int>>
     */
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        return $this->run($query, $bindings, static function () use ($query, $bindings): array {
            if (str_contains($query, 'relation_parents')) {
                return [['id' => 1], ['id' => 2], ['id' => 3]];
            }

            return [['id' => 1, 'relation_parent_id' => (int) $bindings[0]]];
        });
    }

    /**
     * Returns a fixed query time so feature tests do not depend on machine speed.
     */
    protected function getElapsedTime($start)
    {
        return 0.1;
    }
}
