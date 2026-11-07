<?php

namespace Goodport\QueryDuplicates\Tests\Feature\Admin;

use Goodport\QueryDuplicates\Tests\TestCase;

final class DisabledRouteTest extends TestCase
{
    protected bool $adminRoutesEnabled = false;

    /**
     * The admin routes are not registered when they are disabled.
     */
    public function test_routes_are_not_registered_when_disabled(): void
    {
        $this->assertNull(
            $this->app['router']->getRoutes()->getByName('query-duplicates.index'),
        );

        $prefix = (string) config('query-duplicates.admin_route.prefix');

        $this->get('/'.$prefix)->assertNotFound();
    }
}
