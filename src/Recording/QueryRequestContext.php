<?php

namespace Goodport\QueryDuplicates\Recording;

final readonly class QueryRequestContext
{
    public function __construct(
        public string $method,
        public string $path,
        public ?string $route,
    ) {}
}
