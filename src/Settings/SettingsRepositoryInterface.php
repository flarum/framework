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
     * @todo remove $default in 2.0
     *
     * @param $key
     * @param mixed $default: Deprecated
     * @return mixed
     */
    public function get($key, $default = null);

    public function set($key, $value);

    public function delete($keyLike);
}
