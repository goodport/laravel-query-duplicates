<?php

namespace Goodport\QueryDuplicates\Logging;

final readonly class QueryRequestContext
{
    public function __construct(
        public string $method,
        public string $path,
        public ?string $route,
    ) {}
}
