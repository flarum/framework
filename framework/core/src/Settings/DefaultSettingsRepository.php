<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Settings;

use Illuminate\Support\Collection;

class DefaultSettingsRepository implements SettingsRepositoryInterface
{
    public function __construct(
        private readonly SettingsRepositoryInterface $inner,
        protected Collection $defaults
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        // Global default overrules local default because local default is deprecated,
        // and will be removed in 2.0
        return $this->inner->get($key, $this->defaults->get($key, $default));
    }

    public function set(string $key, mixed $value): void
    {
        $this->inner->set($key, $value);
    }

    public function delete(string $keyLike): void
    {
        $this->inner->delete($keyLike);
    }

    public function all(): array
    {
        return array_merge($this->defaults->toArray(), $this->inner->all());
    }
}
