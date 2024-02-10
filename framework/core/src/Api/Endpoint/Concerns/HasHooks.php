<?php

namespace Flarum\Api\Endpoint\Concerns;

use Closure;
use Flarum\Api\Context;

trait HasHooks
{
    protected ?Closure $before = null;
    protected ?Closure $after = null;

    public function before(Closure $callback): static
    {
        $this->before = $callback;

        return $this;
    }

    public function after(Closure $callback): static
    {
        $this->after = $callback;

        return $this;
    }

    protected function callBeforeHook(Context $context): void
    {
        if ($this->before) {
            ($this->before)($context);
        }
    }

    protected function callAfterHook(Context $context, mixed $data): mixed
    {
        if ($this->after) {
            return ($this->after)($context, $data);
        }

        return $data;
    }
}
