<?php

namespace Flarum\Api\Endpoint;

use Flarum\Api\Endpoint\Concerns\HasAuthorization;
use Flarum\Api\Endpoint\Concerns\HasCustomHooks;
use Tobyz\JsonApiServer\Endpoint\Update as BaseUpdate;

class Update extends BaseUpdate implements EndpointInterface
{
    use HasAuthorization;
    use HasCustomHooks;

    public function setUp(): void
    {
        parent::setUp();
    }
}
