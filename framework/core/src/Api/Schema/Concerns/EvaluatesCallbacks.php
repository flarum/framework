<?php

namespace Flarum\Api\Schema\Concerns;

use Tobyz\JsonApiServer\Context;

trait EvaluatesCallbacks
{
    protected function evaluate(Context $context, mixed $callback): mixed
    {
        if (is_string($callback) || ! is_callable($callback)) {
            return $callback;
        }

        return (isset($context->model))
            ? $callback($context->model, $context)
            : $callback($context);
    }
}
