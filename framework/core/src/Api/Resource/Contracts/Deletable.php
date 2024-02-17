<?php

namespace Flarum\Api\Resource\Contracts;

use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Resource\Deletable as BaseDeletable;

interface Deletable extends BaseDeletable
{
    public function deleteAction(object $model, Context $context): void;
}
