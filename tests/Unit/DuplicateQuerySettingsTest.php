<?php

namespace Goodport\QueryDuplicates\Tests\Unit;

use Goodport\QueryDuplicates\Config\DuplicateQuerySettings;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DuplicateQuerySettingsTest extends TestCase
{
    /**
     * The bundled configuration has the documented defaults.
     */
    public function test_default_values_are_loaded(): void
    {
        $settings = DuplicateQuerySettings::fromConfig($this->config());

        $this->assertTrue($settings->enabled);
        $this->assertFalse($settings->strict);
        $this->assertSame(2, $settings->threshold);
        $this->assertNull($settings->logChannel);
        $this->assertSame([], $settings->exclude);
    }

    /**
     * The bundled configuration is enabled and uses the default log channel.
     */
    public function test_bundled_config_is_enabled_by_default(): void
    {
        $settings = DuplicateQuerySettings::fromConfig(
            require dirname(__DIR__, 2).'/config/query-duplicates.php'
        );

        $this->assertTrue($settings->enabled);
        $this->assertNull($settings->logChannel);
    }

    /**
     * A threshold below two cannot detect duplicates and is invalid.
     */
    public function test_threshold_below_two_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('query-duplicates.threshold');

        DuplicateQuerySettings::fromConfig($this->config(['threshold' => 1]));
    }

    /**
     * An empty exclusion substring is invalid.
     */
    public function test_empty_exclude_entry_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Each [query-duplicates.exclude] entry');

        DuplicateQuerySettings::fromConfig($this->config(['exclude' => ['']]));
    }

    /**
     * The strict flag must be a boolean.
     */
    public function test_non_boolean_strict_value_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('query-duplicates.strict');

        DuplicateQuerySettings::fromConfig($this->config(['strict' => 'yes']));
    }

    /**
     * The rest of the configuration is not validated when the package is disabled.
     */
    public function test_invalid_config_is_ignored_when_disabled(): void
    {
        $settings = DuplicateQuerySettings::fromConfig($this->config([
            'enabled' => false,
            'strict' => 'yes',
            'threshold' => 1,
            'exclude' => [''],
        ]));

        $this->assertFalse($settings->enabled);
        $this->assertFalse($settings->strict);
        $this->assertSame(2, $settings->threshold);
        $this->assertSame([], $settings->exclude);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function config(array $overrides = []): array
    {
        $config = require dirname(__DIR__, 2).'/config/query-duplicates.php';

        return array_replace_recursive($config, ['enabled' => true], $overrides);
    }
}
