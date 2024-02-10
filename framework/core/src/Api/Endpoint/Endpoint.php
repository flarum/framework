<?php

namespace Flarum\Api\Endpoint;

use Psr\Http\Message\ResponseInterface as Response;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Endpoint\Endpoint as BaseEndpoint;

interface Endpoint extends BaseEndpoint
{
    /** @var \Flarum\Api\Context $context */
    public function handle(Context $context): ?Response;

    public function execute(Context $context): mixed;

    public function route(): EndpointRoute;
}
