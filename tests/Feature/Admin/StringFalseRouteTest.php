<?php

namespace Goodport\QueryDuplicates\Tests\Feature\Admin;

use Goodport\QueryDuplicates\Tests\TestCase;
use Illuminate\Contracts\Foundation\Application;

final class StringFalseRouteTest extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('query-duplicates.admin_route', [
            'enabled' => 'false',
        ]);
    }

    /**
     * Строковое 'false' в конфиге не регистрирует административные маршруты.
     */
    public function test_string_false_disables_routes(): void
    {
        $this->assertFalse(config('query-duplicates.admin_route.enabled'));

        $this->assertNull(
            $this->app['router']->getRoutes()->getByName('query-duplicates.index'),
        );
    }
}
