<?php

namespace Flarum\Api\Endpoint;

class EndpointRoute
{
    public function __construct(
        public string $name,
        public string $path,
        public string $method,
    ) {
    }
}
