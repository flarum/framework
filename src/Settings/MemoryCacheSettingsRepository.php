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

    protected $defaultSettingsManager;

    protected $isCached;

    protected $cache = [];

    public function __construct(SettingsRepositoryInterface $inner, DefaultSettingsManager $defaultSettingsManager)
    {
        $this->inner = $inner;
        $this->defaultSettingsManager = $defaultSettingsManager;
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
        $value = $this->defaultSettingsManager->get($key, $default);

        if (array_key_exists($key, $this->cache)) {
            $value = $this->cache[$key];
        } elseif (! $this->isCached) {
            $value = Arr::get($this->all(), $key, $value);
        }

        return $value;
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
