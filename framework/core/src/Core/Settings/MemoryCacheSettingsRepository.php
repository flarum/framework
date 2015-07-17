<?php

namespace Flarum\Core\Settings;

class MemoryCacheSettingsRepository implements SettingsRepository
{
    protected $inner;

    protected $isCached;

    protected $cache = [];

    public function __construct(SettingsRepository $inner)
    {
        $this->inner = $inner;
    }

    public function all()
    {
        if (!$this->isCached) {
            $this->cache = $this->inner->all();
            $this->isCached = true;
        }

        return $this->cache;
    }

    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        } else if (!$this->isCached) {
            return array_get($this->all(), $key, $default);
        }

        return $default;
    }

    public function set($key, $value)
    {
        $this->cache[$key] = $value;

        $this->inner->set($key, $value);
    }
}
