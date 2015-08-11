<?php namespace Flarum\Install\Console;

class DefaultData implements ProvidesData
{
    public function getDatabaseConfiguration()
    {
        return [
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'database'  => 'flarum',
            'username'  => 'root',
            'password'  => 'root',
            'prefix'    => '',
        ];
    }

    public function getAdminUser()
    {
        return [
            'username'              => 'admin',
            'password'              => 'admin',
            'email'                 => 'admin@example.com',
        ];
    }
}
