<?php

namespace Goodport\QueryDuplicates\Tests\Feature\Admin;

use Goodport\QueryDuplicates\Config\AdminRouteDefaults;
use Goodport\QueryDuplicates\Tests\TestCase;
use Illuminate\Contracts\Foundation\Application;

final class PartialConfigTest extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('query-duplicates.admin_route', [
            'prefix' => 'partial-query-duplicates',
        ]);
    }

    /**
     * A partial admin route config keeps the safe gate middleware and stays enabled.
     */
    public function test_partial_admin_route_keeps_safe_defaults(): void
    {
        $this->assertTrue(config('query-duplicates.admin_route.enabled'));
        $this->assertSame(
            AdminRouteDefaults::MIDDLEWARE,
            config('query-duplicates.admin_route.middleware'),
        );
    }

    /**
     * The page still returns 403 without the gate after a partial config merge.
     */
    public function test_partial_admin_route_is_forbidden_without_gate(): void
    {
        $this->get(route('query-duplicates.index'))->assertForbidden();
    }
}
