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

use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Yaml;

class FileDataProvider implements DataProviderInterface
{
    protected $debug = false;
    protected $baseUrl = null;
    protected $databaseConfiguration = [];
    protected $adminUser = [];
    protected $settings = [];

    public function __construct(InputInterface $input)
    {
        // Get configuration file path
        $configurationFile = $input->getOption('file');

        // Check if file exists before parsing content
        if (file_exists($configurationFile)) {
            $configurationFileContents = file_get_contents($configurationFile);
            // Try parsing JSON
            if (($json = json_decode($configurationFileContents, true)) !== null) {
                //Use JSON if Valid
                $configuration = $json;
            } else {
                //Else use YAML
                $configuration = Yaml::parse($configurationFileContents);
            }

            // Define configuration variables
            $this->debug = $configuration['debug'] ?? false;
            $this->baseUrl = isset($configuration['baseUrl']) ? rtrim($configuration['baseUrl'], '/') : null;
            $this->databaseConfiguration = $configuration['databaseConfiguration'] ?? [];
            $this->adminUser = $configuration['adminUser'] ?? [];
            $this->settings = $configuration['settings'] ?? [];
        } else {
            throw new Exception('Configuration file does not exist.');
        }
    }

    public function getDatabaseConfiguration()
    {
        return $this->databaseConfiguration + [
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
    }

    public function getBaseUrl()
    {
        return $this->baseUrl ?? 'http://flarum.local';
    }

    public function getAdminUser()
    {
        return $this->adminUser + [
            'username'              => 'admin',
            'password'              => 'password',
            'password_confirmation' => 'password',
            'email'                 => 'admin@example.com',
        ];
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function isDebugMode(): bool
    {
        return $this->debug;
    }
}
