<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Settings;

use Illuminate\Support\Arr;

class MemoryCacheSettingsRepository implements SettingsRepositoryInterface
{
    protected $inner;

    protected $isCached;

    protected $cache = [];

    public function __construct(SettingsRepositoryInterface $inner)
    {
        $this->inner = $inner;
    }

    public function all(): array
    {
        if (! $this->isCached) {
            $this->cache = $this->inner->all();
            $this->isCached = true;
        }

        return $this->cache;
    }

    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        } elseif (! $this->isCached) {
            return Arr::get($this->all(), $key, $default);
        }

        return $default;
    }

    public function set($key, $value)
    {
        $this->cache[$key] = $value;

        $this->inner->set($key, $value);
    }

    public function delete($key)
    {
        unset($this->cache[$key]);

        $this->inner->delete($key);
    }
}
