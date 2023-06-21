<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Settings;

use Illuminate\Database\ConnectionInterface;

class DatabaseSettingsRepository implements SettingsRepositoryInterface
{
    public function __construct(
        protected ConnectionInterface $database
    ) {
    }

    public function all(): array
    {
        return $this->database->table('settings')->pluck('value', 'key')->all();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (is_null($value = $this->database->table('settings')->where('key', $key)->value('value'))) {
            return $default;
        }

        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        $query = $this->database->table('settings')->where('key', $key);

        $method = $query->exists() ? 'update' : 'insert';

        $query->$method(compact('key', 'value'));
    }

    public function delete(string $keyLike): void
    {
        $this->database->table('settings')->where('key', $keyLike)->delete();
    }
}
