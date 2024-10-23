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
    protected bool $isCached = false;
    protected array $cache = [];

    public function __construct(
        protected SettingsRepositoryInterface $inner
    ) {
    }

    public function all(): array
    {
        if (! $this->isCached) {
            $this->cache = $this->inner->all();
            $this->isCached = true;
        }

        return $this->cache;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        } elseif (! $this->isCached) {
            return Arr::get($this->all(), $key, $default);
        }

        return $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->cache[$key] = $value;

        $this->inner->set($key, $value);
    }

    public function delete(string $keyLike): void
    {
        unset($this->cache[$keyLike]);

        $this->inner->delete($keyLike);
    }
}
