<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Settings;

interface SettingsRepositoryInterface
{
    public function all(): array;

    /**
     * @todo remove deprecated $default in 2.0
     */
    public function get(string $key, $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function delete(string $keyLike): void;
}
