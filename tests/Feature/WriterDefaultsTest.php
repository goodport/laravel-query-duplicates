<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Tests\TestCase;

final class WriterDefaultsTest extends TestCase
{
    protected ?string $logChannel = null;

    /**
     * Without a channel, the report goes to the default channel.
     */
    public function test_default_channel_is_used(): void
    {
        $this->get(route('testing.duplicates.identical'))->assertOk();

        $records = $this->loggedRecords('default');

        $this->assertCount(1, $records);
        $this->assertSame('WARNING', $records[0]['level_name']);
        $this->assertSame([], $this->loggedRecords());
    }
}
