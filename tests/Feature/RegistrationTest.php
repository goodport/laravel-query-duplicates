<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Http\TrackDuplicateQueries;
use Goodport\QueryDuplicates\QueryDuplicatesServiceProvider;
use Goodport\QueryDuplicates\Tests\TestCase;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Routing\Events\RouteMatched;

final class RegistrationTest extends TestCase
{
    /**
     * The provider puts the middleware first in the global stack.
     */
    public function test_middleware_is_prepended_to_global_stack(): void
    {
        $middleware = $this->httpKernel()->getGlobalMiddleware();

        $this->assertSame(TrackDuplicateQueries::class, $middleware[0]);
    }

    /**
     * The provider registers the QueryExecuted listener.
     */
    public function test_query_listener_is_registered(): void
    {
        $this->assertTrue(
            $this->app->make(Dispatcher::class)->hasListeners(QueryExecuted::class),
        );
    }

    /**
     * The provider registers the RouteMatched listener for scoped exclusions.
     */
    public function test_route_matched_listener_is_registered(): void
    {
        $this->assertTrue(
            $this->app->make(Dispatcher::class)->hasListeners(RouteMatched::class),
        );
    }

    /**
     * A repeated boot does not register the listener and middleware twice.
     */
    public function test_repeated_boot_registers_listener_and_middleware_once(): void
    {
        $provider = $this->app->getProvider(QueryDuplicatesServiceProvider::class);
        $provider->boot();

        $listeners = $this->app->make(Dispatcher::class)->getListeners(QueryExecuted::class);
        $routeListeners = $this->app->make(Dispatcher::class)->getListeners(RouteMatched::class);
        $middleware = $this->httpKernel()->getGlobalMiddleware();

        $this->assertCount(1, $listeners);
        $this->assertCount(1, $routeListeners);
        $this->assertCount(1, array_keys($middleware, TrackDuplicateQueries::class, true));
    }
}
