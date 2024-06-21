<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Endpoint;

use Flarum\Api\Endpoint\Concerns\ExtractsListingParams;
use Flarum\Api\Endpoint\Concerns\HasAuthorization;
use Flarum\Api\Endpoint\Concerns\HasCustomHooks;
use Tobyz\JsonApiServer\Endpoint\Endpoint as BaseEndpoint;

class Endpoint extends BaseEndpoint implements EndpointInterface
{
    use HasAuthorization;
    use HasCustomHooks;
    use ExtractsListingParams;
}
