<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Install\Steps;

use Flarum\Install\Step;

class BuildConfig implements Step
{
    private $debugMode;

    private $dbConfig;

    private $baseUrl;

    private $store;

    public function __construct($debugMode, $dbConfig, $baseUrl, callable $store)
    {
        $this->debugMode = $debugMode;
        $this->dbConfig = $dbConfig;
        $this->baseUrl = $baseUrl;

        $this->store = $store;
    }

    public function getMessage()
    {
        return 'Building config array';
    }

    public function run()
    {
        $config = [
            'debug'    => $this->debugMode,
            'database' => $this->getDatabaseConfig(),
            'url'      => $this->baseUrl,
            'paths'    => $this->getPathsConfig(),
        ];

        ($this->store)($config);
    }

    private function getDatabaseConfig()
    {
        return [
            'driver'    => $this->dbConfig['driver'],
            'host'      => $this->dbConfig['host'],
            'database'  => $this->dbConfig['database'],
            'username'  => $this->dbConfig['username'],
            'password'  => $this->dbConfig['password'],
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => $this->dbConfig['prefix'],
            'port'      => $this->dbConfig['port'],
            'strict'    => false,
        ];
    }

    private function getPathsConfig()
    {
        return [
            'api'   => 'api',
            'admin' => 'admin',
        ];
    }
}
