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
     * You generally should use the Settings extender's `default` method instead to register default values.
     * You may still need to use the `$default` parameters here in cases where you need to
     * access the default value of a dynamic setting.
     *
     * @see Settings::default()
     */
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function delete(string $keyLike): void;
}
