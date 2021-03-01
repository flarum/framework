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
    protected $database;

    public function __construct(ConnectionInterface $connection)
    {
        $this->database = $connection;
    }

    public function all(): array
    {
        return $this->database->table('settings')->pluck('value', 'key')->all();
    }

    public function get($key, $default = null)
    {
        if (is_null($value = $this->database->table('settings')->where('key', $key)->value('value'))) {
            return $default;
        }

        return $value;
    }

    public function set($key, $value)
    {
        $query = $this->database->table('settings')->where('key', $key);

        $method = $query->exists() ? 'update' : 'insert';

        $query->$method(compact('key', 'value'));
    }

    public function delete($key)
    {
        $this->database->table('settings')->where('key', $key)->delete();
    }
}
