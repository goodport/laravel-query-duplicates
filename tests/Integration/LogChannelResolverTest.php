<?php

namespace Goodport\QueryDuplicates\Tests\Integration;

use Goodport\QueryDuplicates\Logging\LogChannelResolver;
use Goodport\QueryDuplicates\Tests\TestCase;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

final class LogChannelResolverTest extends TestCase
{
    /**
     * A declared channel resolves to a logger.
     */
    public function test_declared_channel_returns_logger(): void
    {
        $this->app['config']->set('logging.channels.extra', [
            'driver' => 'single',
            'path' => $this->app->storagePath('logs/'.$this->logFileName('extra')),
            'level' => 'debug',
        ]);

        $logger = $this->app->make(LogChannelResolver::class)->resolve('extra');

        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }

    /**
     * Without a channel name, the default logger is returned.
     */
    public function test_null_channel_returns_default_logger(): void
    {
        $logger = $this->app->make(LogChannelResolver::class)->resolve(null);

        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }

    /**
     * A missing channel produces a clear exception.
     */
    public function test_missing_channel_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Log channel [missing-channel] is not defined.');

        $this->app->make(LogChannelResolver::class)->resolve('missing-channel');
    }
}
