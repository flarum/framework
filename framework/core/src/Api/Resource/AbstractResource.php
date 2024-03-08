<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Resource;

use Flarum\Api\Resource\Concerns\Bootable;
use Flarum\Api\Resource\Concerns\Extendable;
use Flarum\Api\Resource\Concerns\HasSortMap;
use Flarum\Api\Resource\Contracts\Collection;
use Flarum\Api\Resource\Contracts\Resource;
use Tobyz\JsonApiServer\Resource\AbstractResource as BaseResource;

abstract class AbstractResource extends BaseResource implements Resource, Collection
{
    use Bootable;
    use Extendable;
    use HasSortMap;
}
