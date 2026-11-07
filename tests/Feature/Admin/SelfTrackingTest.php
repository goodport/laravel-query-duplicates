<?php

namespace Goodport\QueryDuplicates\Tests\Feature\Admin;

use Goodport\QueryDuplicates\Aggregator\DuplicateQueryAggregator;
use Goodport\QueryDuplicates\Tests\TestCase;
use Illuminate\Routing\Router;

final class SelfTrackingTest extends TestCase
{
    /**
     * Adds a probe route under the admin prefix.
     *
     * @param  Router  $router
     */
    protected function defineRoutes($router): void
    {
        parent::defineRoutes($router);

        $prefix = (string) config('query-duplicates.admin_route.prefix');

        $router->get('/'.$prefix.'/tracking-flag', static function (): string {
            return app(DuplicateQueryAggregator::class)->isActive() ? 'active' : 'inactive';
        });
    }

    /**
     * The admin prefix is not tracked.
     */
    public function test_admin_prefix_is_not_tracked(): void
    {
        $prefix = (string) config('query-duplicates.admin_route.prefix');

        $this->get('/'.$prefix.'/tracking-flag')->assertContent('inactive');
    }

    /**
     * Opening the admin page does not create a report about itself.
     */
    public function test_admin_page_does_not_report_itself(): void
    {
        $this->allowQueryDuplicates();

        $this->get(route('query-duplicates.index'))->assertOk();

        $this->assertDuplicateCount(0);
    }
}
