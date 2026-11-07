<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Storage\SqliteStorage;
use Goodport\QueryDuplicates\Storage\StorageUnavailableException;
use Goodport\QueryDuplicates\Tests\TestCase;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery;
use Throwable;

final class RecordFailureTest extends TestCase
{
    /**
     * Storage double that fails until the test restores it.
     */
    private SqliteStorage $storage;

    /**
     * Makes the package storage unavailable and expects exactly one reported failure.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $handler = Mockery::mock(ExceptionHandler::class);
        $handler->shouldReceive('report')
            ->once()
            ->with(Mockery::type(Throwable::class));

        $this->app->instance(ExceptionHandler::class, $handler);

        $this->storage = new class extends SqliteStorage
        {
            public bool $available = false;

            public function ensure(): void
            {
                if (! $this->available) {
                    throw new StorageUnavailableException(
                        $this->path(),
                        'simulated storage failure',
                    );
                }

                parent::ensure();
            }
        };

        $this->app->instance(SqliteStorage::class, $this->storage);
    }

    /**
     * A storage failure does not break the response and is reported once per process.
     */
    public function test_storage_failure_does_not_break_response_and_reports_once(): void
    {
        $this->get(route('testing.duplicates.identical'))->assertOk();
        $this->get(route('testing.duplicates.identical'))->assertOk();

        $this->assertDuplicateCount(0);
    }

    /**
     * Recording resumes after the storage becomes available again.
     */
    public function test_recording_resumes_after_storage_is_restored(): void
    {
        $this->get(route('testing.duplicates.identical'))->assertOk();

        $this->assertDuplicateCount(0);

        $this->storage->available = true;

        $this->get(route('testing.duplicates.identical'))->assertOk();

        $this->assertDuplicateCount(1);
        $this->assertSame(1, $this->singleDuplicate()->occurrences);
    }
}
