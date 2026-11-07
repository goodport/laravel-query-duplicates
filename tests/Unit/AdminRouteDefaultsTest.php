<?php

namespace Goodport\QueryDuplicates\Tests\Unit;

use Goodport\QueryDuplicates\Config\AdminRouteDefaults;
use PHPUnit\Framework\TestCase;

final class AdminRouteDefaultsTest extends TestCase
{
    /**
     * Значения по умолчанию содержат полный набор настроек.
     */
    public function test_defaults_are_complete(): void
    {
        $this->assertSame([
            'enabled' => true,
            'prefix' => 'query-duplicates',
            'middleware' => ['web', 'can:view-query-duplicates'],
        ], AdminRouteDefaults::all());
    }

    /**
     * Строка 'false' выключает маршруты, а не превращается в true.
     */
    public function test_string_false_disables_routes(): void
    {
        $adminRoute = AdminRouteDefaults::normalize(['enabled' => 'false']);

        $this->assertFalse($adminRoute['enabled']);
    }

    /**
     * Строка 'true' включает маршруты.
     */
    public function test_string_true_enables_routes(): void
    {
        $adminRoute = AdminRouteDefaults::normalize(['enabled' => 'true']);

        $this->assertTrue($adminRoute['enabled']);
    }

    /**
     * Некорректное значение enabled заменяется безопасным значением по умолчанию.
     */
    public function test_invalid_enabled_falls_back_to_default(): void
    {
        $adminRoute = AdminRouteDefaults::normalize(['enabled' => 'unexpected']);

        $this->assertTrue($adminRoute['enabled']);
    }

    /**
     * Частичный конфиг не теряет middleware с проверкой доступа.
     */
    public function test_partial_config_keeps_gate_middleware(): void
    {
        $adminRoute = AdminRouteDefaults::normalize(['prefix' => 'custom-prefix']);

        $this->assertSame('custom-prefix', $adminRoute['prefix']);
        $this->assertTrue($adminRoute['enabled']);
        $this->assertSame(['web', 'can:view-query-duplicates'], $adminRoute['middleware']);
    }

    /**
     * Не-массивный конфиг заменяется значениями по умолчанию.
     */
    public function test_non_array_config_uses_defaults(): void
    {
        $this->assertSame(AdminRouteDefaults::all(), AdminRouteDefaults::normalize('unexpected'));
    }

    /**
     * Некорректные prefix и middleware заменяются значениями по умолчанию.
     */
    public function test_invalid_prefix_and_middleware_use_defaults(): void
    {
        $adminRoute = AdminRouteDefaults::normalize([
            'prefix' => '',
            'middleware' => 'web',
        ]);

        $this->assertSame('query-duplicates', $adminRoute['prefix']);
        $this->assertSame(['web', 'can:view-query-duplicates'], $adminRoute['middleware']);
    }

    /**
     * Нестроковые middleware отбрасываются, строковые сохраняются.
     */
    public function test_non_string_middleware_items_are_removed(): void
    {
        $adminRoute = AdminRouteDefaults::normalize([
            'middleware' => ['web', 123, null, '', 'auth'],
        ]);

        $this->assertSame(['web', 'auth'], $adminRoute['middleware']);
    }

    /**
     * Если после фильтрации middleware пуст, возвращается безопасный default.
     */
    public function test_middleware_without_valid_strings_falls_back_to_default(): void
    {
        $adminRoute = AdminRouteDefaults::normalize([
            'middleware' => [123, null, ''],
        ]);

        $this->assertSame(AdminRouteDefaults::MIDDLEWARE, $adminRoute['middleware']);
    }

    /**
     * Явные значения конфига сохраняются без изменений.
     */
    public function test_custom_values_are_preserved(): void
    {
        $adminRoute = AdminRouteDefaults::normalize([
            'enabled' => false,
            'prefix' => 'custom-prefix',
            'middleware' => ['web', 'auth'],
        ]);

        $this->assertSame([
            'enabled' => false,
            'prefix' => 'custom-prefix',
            'middleware' => ['web', 'auth'],
        ], $adminRoute);
    }
}
