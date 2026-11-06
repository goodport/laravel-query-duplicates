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
     * A string exclusion is kept as a SQL substring.
     */
    public function test_string_exclusion_is_kept(): void
    {
        $settings = DuplicateQuerySettings::fromConfig($this->config([
            'exclude' => ['from `sessions`'],
        ]));

        $this->assertSame(['from `sessions`'], $settings->exclude);
    }

    /**
     * An array exclusion is normalized with its bindings.
     */
    public function test_array_exclusion_is_normalized(): void
    {
        $settings = DuplicateQuerySettings::fromConfig($this->config([
            'exclude' => [
                [
                    'sql' => 'select * from `example_events` where `category` = ?',
                    'bindings' => [0 => 'security'],
                ],
            ],
        ]));

        $this->assertSame([
            [
                'sql' => 'select * from `example_events` where `category` = ?',
                'bindings' => [0 => 'security'],
            ],
        ], $settings->exclude);
    }

    /**
     * Bindings are optional and default to an empty map.
     */
    public function test_rule_without_bindings_defaults_to_empty_map(): void
    {
        $settings = DuplicateQuerySettings::fromConfig($this->config([
            'exclude' => [
                ['sql' => 'from `sessions`'],
            ],
        ]));

        $this->assertSame([
            ['sql' => 'from `sessions`', 'bindings' => []],
        ], $settings->exclude);
    }

    /**
     * An empty rule SQL is invalid.
     */
    public function test_empty_rule_sql_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('query-duplicates.exclude');

        DuplicateQuerySettings::fromConfig($this->config([
            'exclude' => [['sql' => '']],
        ]));
    }

    /**
     * A non-string rule SQL is invalid.
     */
    public function test_non_string_rule_sql_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('query-duplicates.exclude');

        DuplicateQuerySettings::fromConfig($this->config([
            'exclude' => [['sql' => 123]],
        ]));
    }

    /**
     * Rule bindings must be an array.
     */
    public function test_non_array_rule_bindings_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('query-duplicates.exclude');

        DuplicateQuerySettings::fromConfig($this->config([
            'exclude' => [['sql' => 'from `sessions`', 'bindings' => 'security']],
        ]));
    }

    /**
     * A negative binding position is invalid.
     */
    public function test_negative_binding_position_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('query-duplicates.exclude');

        DuplicateQuerySettings::fromConfig($this->config([
            'exclude' => [['sql' => 'from `sessions`', 'bindings' => [-1 => 'security']]],
        ]));
    }

    /**
     * A non-integer binding position is invalid.
     */
    public function test_non_integer_binding_position_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('query-duplicates.exclude');

        DuplicateQuerySettings::fromConfig($this->config([
            'exclude' => [['sql' => 'from `sessions`', 'bindings' => ['category' => 'security']]],
        ]));
    }

    /**
     * Unknown rule keys are invalid.
     */
    public function test_unknown_rule_key_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('query-duplicates.exclude');

        DuplicateQuerySettings::fromConfig($this->config([
            'exclude' => [['sql' => 'from `sessions`', 'binding' => [0 => 'security']]],
        ]));
    }

    /**
     * An entry must be a string or an array rule.
     */
    public function test_invalid_exclusion_entry_type_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('query-duplicates.exclude');

        DuplicateQuerySettings::fromConfig($this->config([
            'exclude' => [123],
        ]));
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
