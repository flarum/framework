<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Settings;

use Illuminate\Support\Arr;

/**
 * A settings repository decorator that allows overriding certain values.
 *
 * The `OverrideSettingsRepository` class decorates another
 * `SettingsRepositoryInterface` instance but allows certain settings to be
 * overridden with predefined values. It does not affect writing methods.
 *
 * Within Flarum, this can be used to test out new setting values in a system
 * before they are committed to the database.
 *
 * @see \Flarum\Forum\ValidateCustomLess For an example usage.
 */
class OverrideSettingsRepository implements SettingsRepositoryInterface
{
    public function __construct(
        protected SettingsRepositoryInterface $inner,
        protected array $overrides
    ) {
    }

    public function all(): array
    {
        return array_merge($this->inner->all(), $this->overrides);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->overrides)) {
            return $this->overrides[$key];
        }

        return Arr::get($this->all(), $key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        $this->inner->set($key, $value);
    }

    public function delete(string $keyLike): void
    {
        $this->inner->delete($keyLike);
    }
}
