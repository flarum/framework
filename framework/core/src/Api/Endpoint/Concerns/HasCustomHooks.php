<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Endpoint\Concerns;

use Flarum\Foundation\ContainerUtil;
use Tobyz\JsonApiServer\Context;

trait HasCustomHooks
{
    protected function resolveCallable(callable|string $callable, Context $context): callable
    {
        if (is_string($callable)) {
            return ContainerUtil::wrapCallback($callable, $context->api->getContainer());
        }

        return $callable;
    }
}
