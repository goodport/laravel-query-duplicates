<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Tests\TestCase;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Throwable;

final class RecordFailureTest extends TestCase
{
    /**
     * Makes the report table missing and expects exactly one reported failure.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $handler = Mockery::mock(ExceptionHandler::class);
        $handler->shouldReceive('report')
            ->once()
            ->with(Mockery::type(Throwable::class));

        $this->app->instance(ExceptionHandler::class, $handler);

        Schema::connection(config('query-duplicates.connection'))->drop('query_duplicates');
    }

    /**
     * A database failure does not break the response and is reported once per process.
     */
    public function test_database_failure_does_not_break_response_and_reports_once(): void
    {
        $this->get(route('testing.duplicates.identical'))->assertOk();
        $this->get(route('testing.duplicates.identical'))->assertOk();
    }

    /**
     * Recording resumes after the missing table is created without restarting.
     */
    public function test_recording_resumes_after_table_is_created(): void
    {
        $this->get(route('testing.duplicates.identical'))->assertOk();

        $this->migrateQueryDuplicateTable();

        $this->get(route('testing.duplicates.identical'))->assertOk();

        $this->assertDuplicateCount(1);
        $this->assertSame(1, $this->singleDuplicate()->occurrences);
    }
}
