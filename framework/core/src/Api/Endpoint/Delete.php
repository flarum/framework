<?php

namespace Flarum\Api\Endpoint;

use Flarum\Api\Endpoint\Concerns\HasAuthorization;
use Tobyz\JsonApiServer\Endpoint\Delete as BaseDelete;

class Delete extends BaseDelete implements EndpointInterface
{
    use HasAuthorization;
}
