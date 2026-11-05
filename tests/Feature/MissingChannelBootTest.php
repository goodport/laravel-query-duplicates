<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Http\TrackDuplicateQueries;
use Goodport\QueryDuplicates\QueryDuplicatesServiceProvider;
use Goodport\QueryDuplicates\Tests\Support\DefinesLogChannels;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel as KernelContract;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase as Orchestra;

final class MissingChannelBootTest extends Orchestra
{
    use DefinesLogChannels;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearLogFiles($this->app);
    }

    protected function tearDown(): void
    {
        $this->clearLogFiles($this->app);

        parent::tearDown();
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $this->defineLogChannels($app);
    }

    /**
     * When the package is enabled, a missing channel throws during boot.
     */
    public function test_boot_fails_when_configured_channel_is_missing(): void
    {
        $provider = $this->makeProvider();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Log channel [missing-channel] is not defined.');

        $provider->boot();
    }

    /**
     * After the channel is fixed, a repeated boot registers the listener and middleware.
     */
    public function test_boot_can_be_retried_after_channel_is_fixed(): void
    {
        $provider = $this->makeProvider();

        try {
            $provider->boot();
            $this->fail('The missing channel exception was expected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Log channel [missing-channel] is not defined.', $exception->getMessage());
        }

        $this->app['config']->set('query-duplicates.log_channel', 'default');

        $provider->boot();

        $listeners = $this->app->make(Dispatcher::class)->getListeners(QueryExecuted::class);

        /** @var HttpKernel $kernel */
        $kernel = $this->app->make(KernelContract::class);
        $middleware = $kernel->getGlobalMiddleware();

        $this->assertCount(1, $listeners);
        $this->assertContains(TrackDuplicateQueries::class, $middleware);
    }

    /**
     * Registers the provider manually so package config is merged before overrides.
     */
    private function makeProvider(): QueryDuplicatesServiceProvider
    {
        $provider = new QueryDuplicatesServiceProvider($this->app);
        $provider->register();

        $this->app['config']->set('query-duplicates.enabled', true);
        $this->app['config']->set('query-duplicates.log_channel', 'missing-channel');

        return $provider;
    }
}
