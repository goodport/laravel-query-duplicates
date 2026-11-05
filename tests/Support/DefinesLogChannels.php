<?php

namespace Goodport\QueryDuplicates\Tests\Support;

use Illuminate\Contracts\Foundation\Application;
use Monolog\Formatter\JsonFormatter;

trait DefinesLogChannels
{
    /**
     * Configures separate log channels with process-unique files.
     */
    protected function defineLogChannels(Application $app): void
    {
        $app['config']->set('logging.default', 'default');
        $app['config']->set('logging.channels.default', [
            'driver' => 'single',
            'path' => $app->storagePath('logs/'.$this->logFileName('default')),
            'level' => 'debug',
            'formatter' => JsonFormatter::class,
        ]);
        $app['config']->set('logging.channels.query-duplicates', [
            'driver' => 'single',
            'path' => $app->storagePath('logs/'.$this->logFileName('query-duplicates')),
            'level' => 'debug',
            'formatter' => JsonFormatter::class,
        ]);
    }

    /**
     * Returns a process-unique log file name.
     */
    protected function logFileName(string $channel): string
    {
        return $channel.'-'.getmypid().'.log';
    }

    /**
     * Removes stale log files before and after a test.
     */
    protected function clearLogFiles(Application $app): void
    {
        foreach (['query-duplicates', 'default', 'extra'] as $channel) {
            $path = $app->storagePath('logs/'.$this->logFileName($channel));

            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
