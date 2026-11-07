<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Tests\TestCase;

final class MethodTrackingTest extends TestCase
{
    /**
     * @var list<string>|null
     */
    protected ?array $methods = ['GET'];

    /**
     * Трекаются только запросы с разрешённым HTTP-методом.
     */
    public function test_only_configured_http_methods_are_tracked(): void
    {
        $this->get(route('testing.duplicates.identical'))->assertOk();
        $this->assertDuplicateCount(1);

        $this->post(route('testing.duplicates.posted'))->assertOk();
        $this->assertDuplicateCount(1);
    }
}
