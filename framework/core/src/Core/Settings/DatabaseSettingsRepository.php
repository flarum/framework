<?php

namespace Flarum\Core\Settings;

use Illuminate\Database\ConnectionInterface;

class DatabaseSettingsRepository implements SettingsRepository
{
    protected $database;

    public function __construct(ConnectionInterface $connection)
    {
        $this->database = $connection;
    }

    public function all()
    {
        return $this->database->table('config')->lists('value', 'key');
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
