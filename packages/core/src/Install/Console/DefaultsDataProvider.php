<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Install\Console;

class DefaultsDataProvider implements DataProviderInterface
{
    protected $databaseConfiguration = [
        'driver'    => 'mysql',
        'host'      => 'localhost',
        'database'  => 'flarum',
        'username'  => 'root',
        'password'  => '',
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '',
        'port'      => '3306',
        'strict'    => false,
    ];

    protected $debug = false;

    protected $baseUrl = 'http://flarum.local';

    protected $adminUser = [
        'username'              => 'admin',
        'password'              => 'password',
        'password_confirmation' => 'password',
        'email'                 => 'admin@example.com',
    ];

    protected $settings = [];

    public function getDatabaseConfiguration()
    {
        return $this->databaseConfiguration;
    }

    public function setDatabaseConfiguration(array $databaseConfiguration)
    {
        $this->databaseConfiguration = $databaseConfiguration;
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function getAdminUser()
    {
        return $this->adminUser;
    }

    public function setAdminUser(array $adminUser)
    {
        $this->adminUser = $adminUser;
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function setSettings(array $settings)
    {
        $this->settings = $settings;
    }

    public function setSetting($key, $value)
    {
        $this->settings[$key] = $value;
    }

    public function isDebugMode(): bool
    {
        return $this->debug;
    }

    public function setDebugMode(bool $debug = true)
    {
        $this->debug = $debug;
    }
}
