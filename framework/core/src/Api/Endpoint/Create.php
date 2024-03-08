<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Endpoint;

use Flarum\Api\Endpoint\Concerns\HasAuthorization;
use Flarum\Api\Endpoint\Concerns\HasCustomHooks;
use Tobyz\JsonApiServer\Endpoint\Create as BaseCreate;

class Create extends BaseCreate implements EndpointInterface
{
    use HasAuthorization;
    use HasCustomHooks;

    public function setUp(): void
    {
        parent::setUp();
    }
}
