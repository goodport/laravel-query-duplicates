<?php

namespace Goodport\QueryDuplicates\Tests\Support;

trait LoadsPackageConfig
{
    /**
     * Returns the bundled package config with top-level overrides.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function packageConfig(array $overrides = []): array
    {
        $config = require dirname(__DIR__, 2).'/config/query-duplicates.php';

        return array_replace($config, $overrides);
    }
}
