<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Settings;

use RuntimeException;

class DefaultSettingsRepository implements SettingsRepositoryInterface
{
    protected $defaults = [];

    private $inner;

    public function setInner(SettingsRepositoryInterface $inner): void
    {
        $this->inner = $inner;
    }

    public function default(string $key, $value): void
    {
        if (isset($this->defaults[$key])) {
            throw new RuntimeException("Cannot modify immutable default setting $key.");
        }

        $this->defaults[$key] = $value;
    }

    public function get($key, $default = null)
    {
        return $this->inner->get($key, $this->defaults[$key] ?? $default);
    }

    public function set($key, $value)
    {
        $this->inner->set($key, $value);
    }

    public function delete($keyLike)
    {
        $this->inner->delete($keyLike);
    }

    public function all(): array
    {
        return array_merge($this->defaults, $this->inner->all());
    }
}
