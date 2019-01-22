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

use Flarum\Install\ReversibleStep;
use Flarum\Install\Step;

class StoreConfig implements Step, ReversibleStep
{
    private $debugMode;

    private $dbConfig;

    private $baseUrl;

    private $configFile;

    public function __construct($debugMode, $dbConfig, $baseUrl, $configFile)
    {
        $this->debugMode = $debugMode;
        $this->dbConfig = $dbConfig;
        $this->baseUrl = $baseUrl;

        $this->configFile = $configFile;
    }

    public function getMessage()
    {
        return 'Writing config file';
    }

    public function run()
    {
        file_put_contents(
            $this->configFile,
            '<?php return '.var_export($this->buildConfig(), true).';'
        );
    }

    public function revert()
    {
        @unlink($this->configFile);
    }

    private function buildConfig()
    {
        return [
            'debug'    => $this->debugMode,
            'database' => $this->getDatabaseConfig(),
            'url'      => $this->baseUrl,
            'paths'    => $this->getPathsConfig(),
        ];
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
