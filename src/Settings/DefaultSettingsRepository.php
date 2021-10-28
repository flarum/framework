<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Settings;

use Illuminate\Support\Arr;
use RuntimeException;

class DefaultSettingsRepository implements SettingsRepositoryInterface
{
    protected $defaults = [];

    public function get($key, $default = null)
    {
        return Arr::get($this->defaults, $key, $default);
    }

    public function set($key, $value)
    {
        if (isset($this->defaults[$key])) {
            throw new RuntimeException("Cannot modify immutable default setting $key.");
        }

        $this->defaults[$key] = $value;
    }

    public function delete($keyLike)
    {
        throw new RuntimeException("Cannot delete immutable default setting $keyLike.");
    }

    public function all(): array
    {
        return $this->defaults;
    }
}
