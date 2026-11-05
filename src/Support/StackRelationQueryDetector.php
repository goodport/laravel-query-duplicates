<?php

namespace Goodport\QueryDuplicates\Support;

final class StackRelationQueryDetector implements RelationQueryDetector
{
    private const FRAME_LIMIT = 50;

    private const VENDOR_SEGMENT = DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR;

    /**
     * @param  string  $packageSourcePath  Package source path, skipped when looking for the application call site.
     */
    public function __construct(
        private readonly string $packageSourcePath,
    ) {}

    /**
     * Returns the first application frame of a lazy relation query.
     *
     * Only Model::getRelationValue() means that a relation was loaded lazily by
     * accessing the property. Eager loading goes through other frames and is not
     * an N+1.
     */
    public function detect(): ?string
    {
        $frames = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, self::FRAME_LIMIT);

        if (! $this->hasLazyRelationFrame($frames)) {
            return null;
        }

        return $this->applicationCallsite($frames);
    }

    /**
     * @param  list<array<string, mixed>>  $frames
     */
    private function hasLazyRelationFrame(array $frames): bool
    {
        foreach ($frames as $frame) {
            if (($frame['function'] ?? null) === 'getRelationValue') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array<string, mixed>>  $frames
     */
    private function applicationCallsite(array $frames): ?string
    {
        $packageSourcePrefix = rtrim($this->packageSourcePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        foreach ($frames as $frame) {
            $file = $frame['file'] ?? null;

            if ($file === null) {
                continue;
            }

            if (str_contains($file, self::VENDOR_SEGMENT)) {
                continue;
            }

            if (str_starts_with($file, $packageSourcePrefix)) {
                continue;
            }

            return $file.':'.($frame['line'] ?? 0);
        }

        return null;
    }
}
