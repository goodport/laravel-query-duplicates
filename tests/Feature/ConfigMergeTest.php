<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\QueryDuplicatesServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

final class ConfigMergeTest extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [QueryDuplicatesServiceProvider::class];
    }

    /**
     * The package config is merged with the application settings.
     */
    public function test_package_config_is_merged(): void
    {
        $defaults = require dirname(__DIR__, 2).'/config/query-duplicates.php';

        $this->assertSame($defaults, config('query-duplicates'));
    }
}
