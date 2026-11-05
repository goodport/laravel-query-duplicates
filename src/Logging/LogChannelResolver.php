<?php

namespace Goodport\QueryDuplicates\Logging;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Log\LogManager;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

final class LogChannelResolver
{
    public function __construct(
        private readonly LogManager $logManager,
        private readonly Repository $config,
    ) {}

    /**
     * Returns the logger for the given channel or the application's default channel.
     *
     * Laravel's log manager replaces a missing channel with the emergency logger,
     * so the channel is checked against the configuration before resolution: otherwise the package
     * would silently write the report to the wrong place. The exception is visible during provider boot.
     *
     * @throws InvalidArgumentException If the channel is not declared in config/logging.php.
     */
    public function resolve(?string $name): LoggerInterface
    {
        if ($name === null) {
            return $this->logManager->driver();
        }

        $channels = $this->config->get('logging.channels', []);

        if (! is_array($channels) || ! array_key_exists($name, $channels)) {
            throw new InvalidArgumentException("Log channel [{$name}] is not defined.");
        }

        return $this->logManager->channel($name);
    }
}
