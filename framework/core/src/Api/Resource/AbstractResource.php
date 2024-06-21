<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Resource;

use Flarum\Api\Context;
use Flarum\Api\Resource\Concerns\Bootable;
use Flarum\Api\Resource\Concerns\Extendable;
use Flarum\Api\Resource\Concerns\HasSortMap;
use Tobyz\JsonApiServer\Resource\AbstractResource as BaseResource;

/**
 * @template M of object
 * @extends BaseResource<M, Context>
 */
abstract class AbstractResource extends BaseResource
{
    use Bootable;
    use Extendable;
    use HasSortMap;
}
