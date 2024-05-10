<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Endpoint\Concerns;

use RuntimeException;
use Tobyz\JsonApiServer\Context;

trait HasHooks
{
    protected array $before = [];
    protected array $after = [];

    public function before(callable|string $callback): static
    {
        $this->before[] = $callback;

        return $this;
    }

    public function after(callable|string $callback): static
    {
        $this->after[] = $callback;

        return $this;
    }

    protected function resolveCallable(callable|string $callable, Context $context): callable
    {
        if (is_string($callable)) {
            return new $callable();
        }

        return $callable;
    }

    protected function callBeforeHook(Context $context): void
    {
        foreach ($this->before as $before) {
            $before = $this->resolveCallable($before, $context);
            $before($context);
        }
    }

    protected function callAfterHook(Context $context, mixed $data): mixed
    {
        foreach ($this->after as $after) {
            $after = $this->resolveCallable($after, $context);
            $data = $after($context, $data);

            if (empty($data)) {
                throw new RuntimeException('The after hook must return the data back.');
            }
        }

        return $data;
    }
}
