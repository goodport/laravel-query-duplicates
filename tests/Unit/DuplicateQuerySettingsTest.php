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
        $this->assertSame([], $settings->exclusions->rules);
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
     * A query-only rule is kept with an empty binding map.
     */
    public function test_query_only_rule_is_kept(): void
    {
        $settings = DuplicateQuerySettings::fromConfig($this->packageConfig([
            'exclude' => [
                ['query' => 'from `sessions`'],
            ],
        ]));

        $rule = $settings->exclusions->rules[0];

        $this->assertCount(1, $settings->exclusions->rules);
        $this->assertNull($rule->route);
        $this->assertSame('from `sessions`', $rule->sql);
        $this->assertSame([], $rule->bindings);
    }

    /**
     * A query rule with bindings is normalized.
     */
    public function test_query_rule_with_bindings_is_normalized(): void
    {
        $settings = DuplicateQuerySettings::fromConfig($this->packageConfig([
            'exclude' => [
                [
                    'query' => [
                        'sql' => 'select * from `example_events` where `category` = ?',
                        'bindings' => [0 => 'security'],
                    ],
                ],
            ],
        ]));

        $rule = $settings->exclusions->rules[0];

        $this->assertNull($rule->route);
        $this->assertSame('select * from `example_events` where `category` = ?', $rule->sql);
        $this->assertSame([0 => 'security'], $rule->bindings);
    }

    /**
     * Bindings are optional and default to an empty map.
     */
    public function test_query_rule_without_bindings_defaults_to_empty_map(): void
    {
        $settings = DuplicateQuerySettings::fromConfig($this->packageConfig([
            'exclude' => [
                ['query' => ['sql' => 'from `sessions`']],
            ],
        ]));

        $this->assertSame([], $settings->exclusions->rules[0]->bindings);
    }

    /**
     * A route-only rule is trimmed and has no query.
     */
    public function test_route_only_rule_is_kept(): void
    {
        $settings = DuplicateQuerySettings::fromConfig($this->packageConfig([
            'exclude' => [
                ['route' => ' admin.* '],
            ],
        ]));

        $rule = $settings->exclusions->rules[0];

        $this->assertSame('admin.*', $rule->route);
        $this->assertNull($rule->sql);
        $this->assertSame([], $rule->bindings);
    }

    /**
     * A rule can combine a route and a query.
     */
    public function test_route_and_query_rule_is_kept(): void
    {
        $settings = DuplicateQuerySettings::fromConfig($this->packageConfig([
            'exclude' => [
                [
                    'route' => 'posts.*',
                    'query' => 'from `sessions`',
                ],
            ],
        ]));

        $rule = $settings->exclusions->rules[0];

        $this->assertSame('posts.*', $rule->route);
        $this->assertSame('from `sessions`', $rule->sql);
        $this->assertSame([], $rule->bindings);
    }

    /**
     * An empty exclude section means no exclusions.
     */
    public function test_empty_exclude_section_is_allowed(): void
    {
        $settings = DuplicateQuerySettings::fromConfig($this->packageConfig([
            'exclude' => [],
        ]));

        $this->assertSame([], $settings->exclusions->rules);
    }

    /**
     * An empty rule is invalid.
     */
    public function test_empty_rule_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => [[]]],
            'must contain [route] or [query]',
        );
    }

    /**
     * A rule entry must be an array.
     */
    public function test_rule_entry_must_be_an_array(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => ['from `sessions`']],
            'Each [query-duplicates.exclude] entry',
        );
    }

    /**
     * Unknown rule keys are rejected.
     */
    public function test_unknown_rule_key_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => [['route' => 'admin.*', 'limit' => 1]]],
            'query-duplicates.exclude',
        );
    }

    /**
     * A route must be a non-empty string.
     */
    public function test_empty_route_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => [['route' => '']]],
            'query-duplicates.exclude',
        );
    }

    /**
     * A query must be a non-empty string or a rule array.
     */
    public function test_empty_query_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => [['query' => '']]],
            'query-duplicates.exclude',
        );
    }

    /**
     * A non-string query is invalid.
     */
    public function test_non_string_query_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => [['query' => 123]]],
            'query-duplicates.exclude',
        );
    }

    /**
     * Unknown query rule keys are rejected.
     */
    public function test_unknown_query_key_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => [['query' => ['sql' => 'from `sessions`', 'limit' => 1]]]],
            'query-duplicates.exclude',
        );
    }

    /**
     * An empty query SQL is invalid.
     */
    public function test_empty_query_sql_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => [['query' => ['sql' => '']]]],
            'query-duplicates.exclude',
        );
    }

    /**
     * A non-string query SQL is invalid.
     */
    public function test_non_string_query_sql_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => [['query' => ['sql' => 123]]]],
            'query-duplicates.exclude',
        );
    }

    /**
     * Query bindings must be an array.
     */
    public function test_non_array_query_bindings_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => [['query' => ['sql' => 'from `sessions`', 'bindings' => 'security']]]],
            'query-duplicates.exclude',
        );
    }

    /**
     * A negative binding position is invalid.
     */
    public function test_negative_binding_position_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => [['query' => ['sql' => 'from `sessions`', 'bindings' => [-1 => 'security']]]]],
            'query-duplicates.exclude',
        );
    }

    /**
     * A non-integer binding position is invalid.
     */
    public function test_non_integer_binding_position_throws_exception(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => [['query' => ['sql' => 'from `sessions`', 'bindings' => ['category' => 'security']]]]],
            'query-duplicates.exclude',
        );
    }

    /**
     * The previous split routes and queries structure is rejected.
     */
    public function test_previous_routes_and_queries_structure_is_rejected(): void
    {
        $this->assertInvalidConfig(
            ['exclude' => ['routes' => [], 'queries' => []]],
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
                ['route' => ''],
                ['query' => ''],
            ],
        ]));

        $this->assertFalse($settings->enabled);
        $this->assertFalse($settings->strict);
        $this->assertEquals(MethodGroupSettings::getDefaults(), $settings->getGroup);
        $this->assertEquals(MethodGroupSettings::otherDefaults(), $settings->otherGroup);
        $this->assertSame([], $settings->exclusions->rules);
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
