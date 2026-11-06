<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Aggregator\QueryGroup;
use Goodport\QueryDuplicates\Tests\Support\FakeQueryConnection;
use Goodport\QueryDuplicates\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Routing\Router;

final class RelationDetectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make('db')->extend(
            'relation',
            static fn (array $config, string $name): FakeQueryConnection => new FakeQueryConnection(
                $name,
                self::selectRelationRows(...),
            ),
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

        $context = $this->singleReportContext();

        $this->assertSame(QueryGroup::TYPE__N_PLUS_ONE, $context['type']);
        $this->assertSame(3, $context['query_count']);
        $this->assertStringContainsString('relation_children', $context['sql']);
    }

    /**
     * Returns fixed relation rows without a real database.
     *
     * @param  array<int, mixed>  $bindings
     * @return list<array<string, int>>
     */
    private static function selectRelationRows(string $query, array $bindings): array
    {
        if (str_contains($query, 'relation_parents')) {
            return [['id' => 1], ['id' => 2], ['id' => 3]];
        }

        return [['id' => 1, 'relation_parent_id' => (int) $bindings[0]]];
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
