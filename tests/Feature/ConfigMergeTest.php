<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Tests\Support\LoadsPackageConfig;
use Goodport\QueryDuplicates\Tests\TestCase;

final class ConfigMergeTest extends TestCase
{
    use LoadsPackageConfig;

    protected ?string $logChannel = null;

    /**
     * The package config is merged with the application settings.
     */
    public function test_package_config_is_merged(): void
    {
        $this->assertSame($this->packageConfig(), config('query-duplicates'));
    }
}
