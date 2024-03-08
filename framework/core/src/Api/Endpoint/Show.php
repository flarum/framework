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
use Tobyz\JsonApiServer\Endpoint\Show as BaseShow;

class Show extends BaseShow implements EndpointInterface
{
    use HasAuthorization;
    use ExtractsListingParams;
    use HasCustomHooks;

    public function setUp(): void
    {
        parent::setUp();
    }
}
