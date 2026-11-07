<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\QueryDuplicatesServiceProvider;
use Goodport\QueryDuplicates\Tests\TestCase;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

final class DisabledTest extends TestCase
{
    protected bool $packageEnabled = false;

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('query-duplicates.strict', 'invalid');
        $app['config']->set('query-duplicates.methods', [
            'get' => ['threshold' => 1],
            'unknown' => [],
        ]);
        $app['config']->set('query-duplicates.exclude', [
            'routes' => [''],
            'queries' => [''],
        ]);
    }

    /**
     * The config is published even when the package is disabled.
     */
    public function test_config_is_published_while_disabled(): void
    {
        $paths = ServiceProvider::pathsToPublish(
            QueryDuplicatesServiceProvider::class,
            'query-duplicates-config',
        );

        $this->assertNotEmpty($paths);
    }

    /**
     * Queries are not tracked when the package is disabled.
     */
    public function test_queries_are_not_logged_while_disabled(): void
    {
        $this->get(route('testing.duplicates.identical'))->assertOk();

        $this->assertDuplicateCount(0);
    }

    /**
     * An invalid config is not validated and does not break boot when enabled=false.
     */
    public function test_invalid_config_does_not_break_boot_while_disabled(): void
    {
        $this->assertFalse(config('query-duplicates.enabled'));
        $this->assertSame('invalid', config('query-duplicates.strict'));
        $this->assertSame(
            [
                'get' => ['threshold' => 1],
                'unknown' => [],
            ],
            config('query-duplicates.methods'),
        );
        $this->assertSame([
            'routes' => [''],
            'queries' => [''],
        ], config('query-duplicates.exclude'));
    }
}
