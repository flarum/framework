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
    protected $inner;

    protected $overrides = [];

    public function __construct(SettingsRepositoryInterface $inner, array $overrides)
    {
        $this->inner = $inner;
        $this->overrides = $overrides;
    }

    public function all(): array
    {
        return array_merge($this->inner->all(), $this->overrides);
    }

    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->overrides)) {
            return $this->overrides[$key];
        }

        return Arr::get($this->all(), $key, $default);
    }

    public function set($key, $value)
    {
        $this->inner->set($key, $value);
    }

    public function delete($key)
    {
        $this->inner->delete($key);
    }
}
