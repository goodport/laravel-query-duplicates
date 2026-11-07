<?php

namespace Goodport\QueryDuplicates\Tests\Support;

use Goodport\QueryDuplicates\Aggregator\DuplicateQueryAggregator;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use RuntimeException;

trait DefinesQueryRoutes
{
    /**
     * Registers the package's test HTTP routes.
     *
     * @param  Router  $router
     */
    protected function defineRoutes($router): void
    {
        $router->get('/duplicates/identical', function () {
            $this->runQueryTimes(3, 'select ? as id', [7]);

            return response('ok');
        })->name('testing.duplicates.identical');

        $router->get('/duplicates/parameter/{id}', function (int $id) {
            $this->runQueryTimes(3, "select * from `example_users` where `id` in ({$id})");

            return response('ok');
        })->name('testing.duplicates.parameter');

        $router->get('/duplicates/repeat/{times}', function (int $times) {
            $this->runQueryTimes($times, 'select ? as id', [7]);

            return response('ok');
        })->whereNumber('times')->name('testing.duplicates.repeat');

        $router->match(['GET', 'POST'], '/duplicates/threshold/{times}', function (int $times) {
            $this->runQueryTimes($times, 'select ? as threshold', [7]);

            return response('ok');
        })->whereNumber('times')->name('testing.duplicates.threshold');

        $router->post('/duplicates/posted', function () {
            $this->runQueryTimes(5, 'select ? as posted', [1]);

            return response('ok');
        })->name('testing.duplicates.posted');

        $router->get('/duplicates/different-bindings', function () {
            foreach ([1, 2, 3] as $id) {
                DB::select('select ? as id', [$id]);
            }

            return response('ok');
        })->name('testing.duplicates.different-bindings');

        $router->get('/duplicates/secondary', function () {
            $this->runQueryTwice('select ? as id', [1], 'secondary');

            return response('ok');
        })->name('testing.duplicates.secondary');

        $router->get('/duplicates/multiple-connections', function () {
            $this->runQueryTwice('select ? as id', [7], 'testing');
            $this->runQueryTwice('select ? as id', [7], 'secondary');

            return response('ok');
        })->name('testing.duplicates.multiple-connections');

        $router->get('/duplicates/excluded-substring', function () {
            $this->runQueryTwice('select 1 as sessions');
            $this->runQueryTwice('select 2 as kept');

            return response('ok');
        })->name('testing.duplicates.excluded-substring');

        $router->get('/duplicates/excluded-bindings', function () {
            $sql = 'select * from `example_events` where `category` = ?';

            $this->runQueryTwice($sql, ['security']);
            $this->runQueryTwice($sql, ['other']);
            $this->runQueryTwice('select 2 as kept');

            return response('ok');
        })->name('testing.duplicates.excluded-bindings');

        $router->get('/duplicates/redirect', function () {
            $this->runQueryTwice('select ? as redirect', [1]);

            return redirect()->route('testing.duplicates.identical');
        })->name('testing.duplicates.redirect');

        $router->get('/duplicates/exception', function () {
            $this->runQueryTwice('select ? as exception', [1]);

            throw new RuntimeException('Route failed.');
        })->name('testing.duplicates.exception');

        $router->get('/duplicates/active-flag', function () {
            $aggregator = app(DuplicateQueryAggregator::class);

            return response($aggregator->isActive() ? 'active' : 'inactive');
        })->name('testing.duplicates.active-flag');

        $router->fallback(function () {
            $this->runQueryTwice('select ? as missing', [404]);

            return response('not found', 404);
        });
    }

    /**
     * Runs the same SQL twice to avoid duplicating the pattern in routes.
     *
     * @param  array<int, mixed>  $bindings
     */
    private function runQueryTwice(string $sql, array $bindings = [], ?string $connection = null): void
    {
        $this->runQueryTimes(2, $sql, $bindings, $connection);
    }

    /**
     * Runs the same SQL the given number of times.
     *
     * @param  array<int, mixed>  $bindings
     */
    protected function runQueryTimes(
        int $times,
        string $sql,
        array $bindings = [],
        ?string $connection = null,
    ): void {
        for ($execution = 0; $execution < $times; $execution++) {
            DB::connection($connection)->select($sql, $bindings);
        }
    }
}
