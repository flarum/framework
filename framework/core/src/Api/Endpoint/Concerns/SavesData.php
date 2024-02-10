<?php

namespace Flarum\Api\Endpoint\Concerns;

use Tobyz\JsonApiServer\Context;

trait SavesData
{
    private function mutateDataBeforeValidation(Context $context, array $data, bool $validateAll): array
    {
        if (method_exists($context->resource, 'mutateDataBeforeValidation')) {
            return $context->resource->mutateDataBeforeValidation($context, $data, $validateAll);
        }

        return $data;
    }
}
