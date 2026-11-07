<?php

namespace Goodport\QueryDuplicates\Tests\Unit;

use Goodport\QueryDuplicates\Config\DuplicateQuerySettings;
use Goodport\QueryDuplicates\Tests\Support\LoadsPackageConfig;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DuplicateQuerySettingsTest extends TestCase
{
    use LoadsPackageConfig;

    /**
     * The bundled configuration has the documented defaults.
     */
    public function test_default_values_are_loaded(): void
    {
        $settings = DuplicateQuerySettings::fromConfig($this->packageConfig());

        $this->assertTrue($settings->enabled);
        $this->assertFalse($settings->strict);
        $this->assertSame(2, $settings->threshold);
        $this->assertSame([], $settings->exclude);
    }

    /**
     * A threshold below two cannot detect duplicates and is invalid.
     */
    public function test_threshold_below_two_throws_exception(): void
    {
        $this->assertInvalidConfig(['threshold' => 1], 'query-duplicates.threshold');
    }

    /**
     * An empty exclusion substring is invalid.
     */
    public function test_empty_exclude_entry_throws_exception(): void
    {
        $this->assertInvalidConfig(['exclude' => ['']], 'Each [query-duplicates.exclude] entry');
    }

    /**
     * A string exclusion is kept as a SQL substring.
     */
    public function test_string_exclusion_is_kept(): void
    {
        $settings = DuplicateQuerySettings::fromConfig($this->packageConfig([
            'exclude' => ['from `sessions`'],
        ]));

        $this->assertSame(['from `sessions`'], $settings->exclude);
    }

    /**
     * An array exclusion is normalized with its bindings.
     */
    public function test_array_exclusion_is_normalized(): void
    {
        $settings = DuplicateQuerySettings::fromConfig($this->packageConfig([
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
        $settings = DuplicateQuerySettings::fromConfig($this->packageConfig([
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
        $this->assertInvalidConfig(['exclude' => [['sql' => '']]], 'query-duplicates.exclude');
    }

    /**
     * A non-string rule SQL is invalid.
     */
    public function test_non_string_rule_sql_throws_exception(): void
    {
        $this->assertInvalidConfig(['exclude' => [['sql' => 123]]], 'query-duplicates.exclude');
    }

    /**
     * Rule bindings must be an array.
     */
    public function test_non_array_rule_bindings_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => [['sql' => 'from `sessions`', 'bindings' => 'security']]],
            'query-duplicates.exclude',
        );
    }

    /**
     * A negative binding position is invalid.
     */
    public function test_negative_binding_position_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => [['sql' => 'from `sessions`', 'bindings' => [-1 => 'security']]]],
            'query-duplicates.exclude',
        );
    }

    /**
     * A non-integer binding position is invalid.
     */
    public function test_non_integer_binding_position_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => [['sql' => 'from `sessions`', 'bindings' => ['category' => 'security']]]],
            'query-duplicates.exclude',
        );
    }

    /**
     * Unknown rule keys are invalid.
     */
    public function test_unknown_rule_key_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => [['sql' => 'from `sessions`', 'binding' => [0 => 'security']]]],
            'query-duplicates.exclude',
        );
    }

    /**
     * An entry must be a string or an array rule.
     */
    public function test_invalid_exclusion_entry_type_throws_exception(): void
    {
        $this->assertInvalidConfig(['exclude' => [123]], 'query-duplicates.exclude');
    }

    /**
     * The strict flag must be a boolean.
     */
    public function test_non_boolean_strict_value_throws_exception(): void
    {
        $this->assertInvalidConfig(['strict' => 'yes'], 'query-duplicates.strict');
    }

    /**
     * The rest of the configuration is not validated when the package is disabled.
     */
    public function test_invalid_config_is_ignored_when_disabled(): void
    {
        $settings = DuplicateQuerySettings::fromConfig($this->packageConfig([
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
     * Asserts that the configuration is rejected with the given message.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function assertInvalidConfig(array $overrides, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        DuplicateQuerySettings::fromConfig($this->packageConfig($overrides));
    }
}
