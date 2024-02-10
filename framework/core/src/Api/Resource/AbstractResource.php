<?php

namespace Flarum\Api\Resource;

use Flarum\Api\Resource\Concerns\Bootable;
use Tobyz\JsonApiServer\Resource\AbstractResource as BaseResource;

abstract class AbstractResource extends BaseResource
{
    use Bootable;
}
