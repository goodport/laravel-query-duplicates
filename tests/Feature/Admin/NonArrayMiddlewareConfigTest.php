<?php

namespace Goodport\QueryDuplicates\Tests\Feature\Admin;

use Goodport\QueryDuplicates\Config\AdminRouteDefaults;
use Goodport\QueryDuplicates\Tests\TestCase;
use Illuminate\Contracts\Foundation\Application;

final class NonArrayMiddlewareConfigTest extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('query-duplicates.admin_route', [
            'middleware' => 'web',
        ]);
    }

    /**
     * A non-array middleware value falls back to the safe default.
     */
    public function test_non_array_middleware_falls_back_to_safe_default(): void
    {
        $this->assertSame(
            AdminRouteDefaults::MIDDLEWARE,
            config('query-duplicates.admin_route.middleware'),
        );
    }

    /**
     * The page stays forbidden without the gate.
     */
    public function test_page_is_forbidden_without_gate(): void
    {
        $this->get(route('query-duplicates.index'))->assertForbidden();
    }
}
