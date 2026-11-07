<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Tests\TestCase;

final class EmptyMethodSettingsTest extends TestCase
{
    /**
     * @var array{get?: array{enabled?: bool, threshold?: int}, other?: array{enabled?: bool, threshold?: int}}|null
     */
    protected ?array $methods = [];

    /**
     * Пустая секция methods включает обе группы с их порогами по умолчанию.
     */
    public function test_empty_methods_section_tracks_post_at_default_threshold(): void
    {
        $this->post(route('testing.duplicates.posted'))->assertOk();

        $this->assertDuplicateCount(1);
    }
}
