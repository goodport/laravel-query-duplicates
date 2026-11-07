<?php

namespace Goodport\QueryDuplicates\Tests\Unit;

use Goodport\QueryDuplicates\Config\DuplicateQuerySettings;
use Goodport\QueryDuplicates\Config\MethodGroupSettings;
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
        $this->assertTrue($settings->getGroup->enabled);
        $this->assertSame(2, $settings->getGroup->threshold);
        $this->assertTrue($settings->otherGroup->enabled);
        $this->assertSame(5, $settings->otherGroup->threshold);
        $this->assertSame([], $settings->exclusions->routes);
        $this->assertSame([], $settings->exclusions->queries);
    }

    /**
     * A missing methods section uses both default groups.
     */
    public function test_missing_methods_section_uses_default_groups(): void
    {
        $config = $this->packageConfig();
        unset($config['methods']);

        $settings = DuplicateQuerySettings::fromConfig($config);

        $this->assertEquals(MethodGroupSettings::getDefaults(), $settings->getGroup);
        $this->assertEquals(MethodGroupSettings::otherDefaults(), $settings->otherGroup);
    }

    /**
     * An empty methods section uses both default groups.
     */
    public function test_empty_methods_section_uses_default_groups(): void
    {
        $settings = DuplicateQuerySettings::fromConfig($this->packageConfig([
            'methods' => [],
        ]));

        $this->assertEquals(MethodGroupSettings::getDefaults(), $settings->getGroup);
        $this->assertEquals(MethodGroupSettings::otherDefaults(), $settings->otherGroup);
    }

    /**
     * Custom group values are loaded.
     */
    public function test_custom_group_values_are_loaded(): void
    {
        $settings = DuplicateQuerySettings::fromConfig($this->packageConfig([
            'methods' => [
                'get' => ['enabled' => false, 'threshold' => 4],
                'other' => ['enabled' => true, 'threshold' => 6],
            ],
        ]));

        $this->assertFalse($settings->getGroup->enabled);
        $this->assertSame(4, $settings->getGroup->threshold);
        $this->assertTrue($settings->otherGroup->enabled);
        $this->assertSame(6, $settings->otherGroup->threshold);
    }

    /**
     * A missing group uses its own defaults.
     */
    public function test_missing_group_uses_its_defaults(): void
    {
        $settings = DuplicateQuerySettings::fromConfig($this->packageConfig([
            'methods' => [
                'get' => ['threshold' => 3],
            ],
        ]));

        $this->assertTrue($settings->getGroup->enabled);
        $this->assertSame(3, $settings->getGroup->threshold);
        $this->assertEquals(MethodGroupSettings::otherDefaults(), $settings->otherGroup);
    }

    /**
     * A missing group setting uses the group default.
     */
    public function test_missing_group_setting_uses_group_default(): void
    {
        $settings = DuplicateQuerySettings::fromConfig($this->packageConfig([
            'methods' => [
                'other' => ['enabled' => false],
            ],
        ]));

        $this->assertFalse($settings->otherGroup->enabled);
        $this->assertSame(5, $settings->otherGroup->threshold);
        $this->assertEquals(MethodGroupSettings::getDefaults(), $settings->getGroup);
    }

    /**
     * The methods section must be an array of groups.
     */
    public function test_non_array_methods_section_throws_exception(): void
    {
        $this->assertInvalidConfig(['methods' => 'GET'], 'query-duplicates.methods');
    }

    /**
     * The previous flat method list is rejected.
     */
    public function test_flat_method_list_throws_exception(): void
    {
        $this->assertInvalidConfig(['methods' => ['GET']], 'query-duplicates.methods');
    }

    /**
     * Unknown method groups are rejected.
     */
    public function test_unknown_method_group_throws_exception(): void
    {
        $this->assertInvalidConfig(['methods' => ['post' => []]], 'query-duplicates.methods');
    }

    /**
     * A group value must be an array.
     */
    public function test_non_array_group_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['methods' => ['get' => 'GET']],
            'query-duplicates.methods.get',
        );
    }

    /**
     * A group enabled flag must be a boolean.
     */
    public function test_non_boolean_group_enabled_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['methods' => ['get' => ['enabled' => 'yes']]],
            'query-duplicates.methods.get.enabled',
        );
    }

    /**
     * A threshold below two cannot detect duplicates and is invalid.
     */
    public function test_threshold_below_two_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['methods' => ['other' => ['threshold' => 1]]],
            'query-duplicates.methods.other.threshold',
        );
    }

    /**
     * A non-integer threshold is invalid.
     */
    public function test_non_integer_threshold_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['methods' => ['other' => ['threshold' => 'many']]],
            'query-duplicates.methods.other.threshold',
        );
    }

    /**
     * Unknown group settings are rejected.
     */
    public function test_unknown_group_setting_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['methods' => ['get' => ['limit' => 3]]],
            'query-duplicates.methods.get',
        );
    }

    /**
     * An empty exclusion substring is invalid.
     */
    public function test_empty_exclude_entry_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => ['queries' => ['']]],
            'Each [query-duplicates.exclude.queries] entry',
        );
    }

    /**
     * A string exclusion is kept as a SQL substring.
     */
    public function test_string_exclusion_is_kept(): void
    {
        $settings = DuplicateQuerySettings::fromConfig($this->packageConfig([
            'exclude' => [
                'queries' => ['from `sessions`'],
            ],
        ]));

        $this->assertSame(['from `sessions`'], $settings->exclusions->queries);
    }

    /**
     * An array exclusion is normalized with its bindings.
     */
    public function test_array_exclusion_is_normalized(): void
    {
        $settings = DuplicateQuerySettings::fromConfig($this->packageConfig([
            'exclude' => [
                'queries' => [
                    [
                        'sql' => 'select * from `example_events` where `category` = ?',
                        'bindings' => [0 => 'security'],
                    ],
                ],
            ],
        ]));

        $this->assertSame([
            [
                'sql' => 'select * from `example_events` where `category` = ?',
                'bindings' => [0 => 'security'],
            ],
        ], $settings->exclusions->queries);
    }

    /**
     * Bindings are optional and default to an empty map.
     */
    public function test_rule_without_bindings_defaults_to_empty_map(): void
    {
        $settings = DuplicateQuerySettings::fromConfig($this->packageConfig([
            'exclude' => [
                'queries' => [
                    ['sql' => 'from `sessions`'],
                ],
            ],
        ]));

        $this->assertSame([
            ['sql' => 'from `sessions`', 'bindings' => []],
        ], $settings->exclusions->queries);
    }

    /**
     * An empty rule SQL is invalid.
     */
    public function test_empty_rule_sql_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => ['queries' => [['sql' => '']]]],
            'query-duplicates.exclude.queries',
        );
    }

    /**
     * A non-string rule SQL is invalid.
     */
    public function test_non_string_rule_sql_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => ['queries' => [['sql' => 123]]]],
            'query-duplicates.exclude.queries',
        );
    }

    /**
     * Rule bindings must be an array.
     */
    public function test_non_array_rule_bindings_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => ['queries' => [['sql' => 'from `sessions`', 'bindings' => 'security']]]],
            'query-duplicates.exclude.queries',
        );
    }

    /**
     * A negative binding position is invalid.
     */
    public function test_negative_binding_position_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => ['queries' => [['sql' => 'from `sessions`', 'bindings' => [-1 => 'security']]]]],
            'query-duplicates.exclude.queries',
        );
    }

    /**
     * A non-integer binding position is invalid.
     */
    public function test_non_integer_binding_position_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => ['queries' => [['sql' => 'from `sessions`', 'bindings' => ['category' => 'security']]]]],
            'query-duplicates.exclude.queries',
        );
    }

    /**
     * Unknown rule keys are invalid.
     */
    public function test_unknown_rule_key_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => ['queries' => [['sql' => 'from `sessions`', 'binding' => [0 => 'security']]]]],
            'query-duplicates.exclude.queries',
        );
    }

    /**
     * An entry must be a string or an array rule.
     */
    public function test_invalid_exclusion_entry_type_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => ['queries' => [123]]],
            'query-duplicates.exclude.queries',
        );
    }

    /**
     * Route exclusions are trimmed and keep wildcard patterns.
     */
    public function test_route_exclusions_are_loaded(): void
    {
        $settings = DuplicateQuerySettings::fromConfig($this->packageConfig([
            'exclude' => [
                'routes' => [' admin.* ', 'health'],
            ],
        ]));

        $this->assertSame(['admin.*', 'health'], $settings->exclusions->routes);
        $this->assertSame([], $settings->exclusions->queries);
    }

    /**
     * Route exclusions must be a list.
     */
    public function test_route_exclusions_must_be_list(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => ['routes' => 'admin.*']],
            'query-duplicates.exclude.routes',
        );
    }

    /**
     * An empty route exclusion is invalid.
     */
    public function test_empty_route_exclusion_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => ['routes' => ['']]],
            'query-duplicates.exclude.routes',
        );
    }

    /**
     * An empty exclude section means no exclusions.
     */
    public function test_empty_exclude_section_is_allowed(): void
    {
        $settings = DuplicateQuerySettings::fromConfig($this->packageConfig([
            'exclude' => [],
        ]));

        $this->assertSame([], $settings->exclusions->routes);
        $this->assertSame([], $settings->exclusions->queries);
    }

    /**
     * The previous flat exclude list is rejected.
     */
    public function test_flat_exclude_list_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => ['from `sessions`']],
            'query-duplicates.exclude',
        );
    }

    /**
     * Unknown exclude keys are rejected.
     */
    public function test_unknown_exclude_key_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => ['routes' => [], 'query' => []]],
            'query-duplicates.exclude',
        );
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
            'methods' => ['get' => ['threshold' => 1], 'unknown' => []],
            'exclude' => [
                'routes' => [''],
                'queries' => [''],
            ],
        ]));

        $this->assertFalse($settings->enabled);
        $this->assertFalse($settings->strict);
        $this->assertEquals(MethodGroupSettings::getDefaults(), $settings->getGroup);
        $this->assertEquals(MethodGroupSettings::otherDefaults(), $settings->otherGroup);
        $this->assertSame([], $settings->exclusions->routes);
        $this->assertSame([], $settings->exclusions->queries);
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
