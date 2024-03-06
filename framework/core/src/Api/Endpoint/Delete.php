<?php

namespace Flarum\Api\Endpoint;

use Flarum\Api\Endpoint\Concerns\HasAuthorization;
use Flarum\Api\Endpoint\Concerns\HasCustomHooks;
use Tobyz\JsonApiServer\Endpoint\Delete as BaseDelete;

class Delete extends BaseDelete implements EndpointInterface
{
    use HasAuthorization;
    use HasCustomHooks;
}
