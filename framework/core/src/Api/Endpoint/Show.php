<?php

namespace Flarum\Api\Endpoint;

use Flarum\Api\Endpoint\Concerns\ExtractsListingParams;
use Flarum\Api\Endpoint\Concerns\HasAuthorization;
use Flarum\Api\Endpoint\Concerns\HasCustomHooks;
use Tobyz\JsonApiServer\Endpoint\Show as BaseShow;

class Show extends BaseShow implements EndpointInterface
{
    use HasAuthorization;
    use ExtractsListingParams;
    use HasCustomHooks;

    public function setUp(): void
    {
        parent::setUp();;
    }
}
