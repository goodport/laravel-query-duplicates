<?php

namespace Goodport\QueryDuplicates\Tests\Feature;

use Goodport\QueryDuplicates\Aggregator\DuplicateQueryAggregator;
use Goodport\QueryDuplicates\Logging\DuplicateQueryWriter;
use Goodport\QueryDuplicates\Tests\TestCase;
use Psr\Log\AbstractLogger;
use RuntimeException;
use Stringable;

final class LifecycleTest extends TestCase
{
    /**
     * Collection is active only inside an HTTP request.
     */
    public function test_collection_is_active_only_inside_http_request(): void
    {
        $aggregator = $this->app->make(DuplicateQueryAggregator::class);

        $this->assertFalse($aggregator->isActive());

        $this->get(route('testing.duplicates.active-flag'))->assertContent('active');

        $this->assertFalse($aggregator->isActive());
    }

    /**
     * Each HTTP request gets its own report without data from the previous one.
     */
    public function test_each_request_gets_its_own_report(): void
    {
        $this->get(route('testing.duplicates.identical'))->assertOk();
        $this->get(route('testing.duplicates.identical'))->assertOk();

        $contexts = $this->reportContexts();

        $this->assertCount(2, $contexts);
        $this->assertSame(3, $contexts[0]['query_count']);
        $this->assertSame(3, $contexts[1]['query_count']);
    }

    /**
     * A redirect produces a separate report for each HTTP request.
     */
    public function test_redirect_gives_report_for_each_request(): void
    {
        $this->followingRedirects()->get(route('testing.duplicates.redirect'))->assertOk();

        $contexts = $this->reportContexts();

        $this->assertCount(2, $contexts);
        $this->assertSame('select ? as redirect', $contexts[0]['sql']);
        $this->assertSame('select ? as id', $contexts[1]['sql']);
    }

    /**
     * With normal exception handling, the route still produces a report.
     */
    public function test_report_is_written_when_route_throws(): void
    {
        $this->get(route('testing.duplicates.exception'))->assertStatus(500);

        $this->assertSame('select ? as exception', $this->singleReportContext()['sql']);
    }

    /**
     * An unhandled exception does not leak into the next request.
     */
    public function test_unhandled_exception_resets_state_between_requests(): void
    {
        $this->withoutExceptionHandling();

        try {
            $this->get(route('testing.duplicates.exception'));
            $this->fail('The route exception was expected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Route failed.', $exception->getMessage());
        }

        $this->assertReportCount(0);

        $this->get(route('testing.duplicates.identical'))->assertOk();

        $context = $this->singleReportContext();

        $this->assertSame('select ? as id', $context['sql']);
        $this->assertSame(3, $context['query_count']);
    }

    /**
     * A writer failure does not leave the aggregator active and does not keep the data.
     */
    public function test_writer_failure_resets_aggregator(): void
    {
        $throwingLogger = new class extends AbstractLogger
        {
            public function log($level, string|Stringable $message, array $context = []): void
            {
                throw new RuntimeException('Writer failed.');
            }
        };

        $this->app->instance(
            DuplicateQueryWriter::class,
            new DuplicateQueryWriter($throwingLogger, 'warning'),
        );

        try {
            $this->get(route('testing.duplicates.identical'))->assertOk();
            $this->fail('The writer exception was expected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Writer failed.', $exception->getMessage());
        }

        $aggregator = $this->app->make(DuplicateQueryAggregator::class);

        $this->assertFalse($aggregator->isActive());
        $this->assertSame([], $aggregator->report());
    }
}
