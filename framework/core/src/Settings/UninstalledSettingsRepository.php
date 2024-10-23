<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Settings;

class UninstalledSettingsRepository implements SettingsRepositoryInterface
{
    public function all(): array
    {
        return [];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    public function set(string $key, mixed $value): void
    {
        // Do nothing
    }

    public function delete(string $keyLike): void
    {
        // Do nothing
    }
}
