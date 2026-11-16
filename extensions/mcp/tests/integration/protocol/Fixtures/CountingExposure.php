<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\protocol\Fixtures;

use Flarum\Api\Resource\AbstractResource;
use Flarum\Mcp\Api\ResourceExposure;
use Flarum\Mcp\Tests\integration\protocol\RequestCostTest;
use Flarum\User\User;

class CountingExposure extends ResourceExposure
{
    public function operations(AbstractResource $resource, User $actor): array
    {
        RequestCostTest::$calls[] = 'operations:'.$resource->type();

        return parent::operations($resource, $actor);
    }
}
