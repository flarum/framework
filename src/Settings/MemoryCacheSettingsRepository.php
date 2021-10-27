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

    protected $defaultSettingsManager;

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

        return $this->getCache();
    }

    public function get($key, $default = null)
    {
        $cache = $this->getCache();

        if (array_key_exists($key, $cache)) {
            return $cache[$key];
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

    protected function getCache(): array
    {
        return array_merge($this->defaultSettingsManager->all(), $this->cache);
    }
}
