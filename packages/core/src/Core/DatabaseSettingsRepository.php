<?php

namespace Flarum\Core;

use Illuminate\Database\ConnectionInterface;

class DatabaseSettingsRepository implements SettingsRepositoryInterface
{
    protected $database;

    public function __construct(ConnectionInterface $connection)
    {
        $this->database = $connection;
    }

    public function get($key, $default = null)
    {
        if (is_null($value = $this->database->table('config')->where('key', $key)->pluck('value'))) {
            return $default;
        }

        return $value;
    }

    public function set($key, $value)
    {
        $this->database->table('config')->where('key', $key)->update(['value' => $value]);
    }
}
