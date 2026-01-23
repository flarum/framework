<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Log\Context;

/**
 * Stub implementation of Laravel's Log Context Repository.
 *
 * Laravel 12's scheduling system expects this class to exist in the container
 * as \Illuminate\Log\Context\Repository. Since Flarum uses a simplified logging
 * setup with Monolog directly, we provide a minimal stub that returns empty
 * context data.
 *
 * This is aliased to \Illuminate\Log\Context\Repository in the container.
 *
 * This is sufficient for Laravel 12 compatibility as Flarum doesn't use
 * Laravel's context system for passing data between processes.
 */
class Repository
{
    /**
     * The context data.
     */
    private array $data = [];

    /**
     * The hidden context data.
     */
    private array $hidden = [];

    /**
     * Determine if the given key exists in the context.
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Determine if the given key is missing from the context.
     */
    public function missing(string $key): bool
    {
        return ! $this->has($key);
    }

    /**
     * Get all context data.
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Get a context value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Add a context value.
     */
    public function add(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * Add a hidden context value.
     */
    public function addHidden(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            $this->hidden = array_merge($this->hidden, $key);
        } else {
            $this->hidden[$key] = $value;
        }

        return $this;
    }

    /**
     * Forget a context key.
     */
    public function forget(string|array $key): self
    {
        $keys = is_array($key) ? $key : [$key];

        foreach ($keys as $k) {
            unset($this->data[$k]);
        }

        return $this;
    }

    /**
     * Forget a hidden context key.
     */
    public function forgetHidden(string|array $key): self
    {
        $keys = is_array($key) ? $key : [$key];

        foreach ($keys as $k) {
            unset($this->hidden[$k]);
        }

        return $this;
    }

    /**
     * Get all hidden context data.
     */
    public function allHidden(): array
    {
        return $this->hidden;
    }

    /**
     * Flush all context data.
     */
    public function flush(): self
    {
        $this->data = [];
        $this->hidden = [];

        return $this;
    }

    /**
     * Dehydrate the context data for serialization.
     *
     * This method is called by Laravel's scheduler when passing context
     * between processes. For Flarum, we return an empty array as we don't
     * use Laravel's context system.
     */
    public function dehydrate(): array
    {
        return [];
    }

    /**
     * Hydrate the context from an array.
     */
    public function hydrate(array $context): self
    {
        return $this;
    }

    /**
     * Register a dehydrating callback.
     */
    public function dehydrating(callable $callback): self
    {
        return $this;
    }

    /**
     * Register a hydrated callback.
     */
    public function hydrated(callable $callback): self
    {
        return $this;
    }
}
