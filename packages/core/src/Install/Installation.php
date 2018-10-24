<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Install;

use Flarum\Install\Prerequisite\Composite;
use Flarum\Install\Prerequisite\PhpExtensions;
use Flarum\Install\Prerequisite\PhpVersion;
use Flarum\Install\Prerequisite\PrerequisiteInterface;
use Flarum\Install\Prerequisite\WritablePaths;
use Flarum\Install\Steps\BuildConfig;
use Flarum\Install\Steps\ConnectToDatabase;
use Flarum\Install\Steps\CreateAdminUser;
use Flarum\Install\Steps\EnableBundledExtensions;
use Flarum\Install\Steps\PublishAssets;
use Flarum\Install\Steps\RunMigrations;
use Flarum\Install\Steps\StoreConfig;
use Flarum\Install\Steps\WriteSettings;

class Installation
{
    private $basePath;
    private $publicPath;
    private $storagePath;

    private $configPath;
    private $debug = false;
    private $dbConfig = [];
    private $baseUrl;
    private $defaultSettings = [];
    private $adminUser = [];

    public function __construct($basePath, $publicPath, $storagePath)
    {
        $this->basePath = $basePath;
        $this->publicPath = $publicPath;
        $this->storagePath = $storagePath;
    }

    public function configPath($path)
    {
        $this->configPath = $path;

        return $this;
    }

    public function debugMode($flag)
    {
        $this->debug = $flag;

        return $this;
    }

    public function databaseConfig(array $dbConfig)
    {
        $this->dbConfig = $dbConfig;

        return $this;
    }

    public function baseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    public function settings($settings)
    {
        $this->defaultSettings = $settings;

        return $this;
    }

    public function adminUser($admin)
    {
        $this->adminUser = $admin;

        return $this;
    }

    public function prerequisites(): PrerequisiteInterface
    {
        return new Composite(
            new PhpVersion('7.1.0'),
            new PhpExtensions([
                'dom',
                'gd',
                'json',
                'mbstring',
                'openssl',
                'pdo_mysql',
                'tokenizer',
            ]),
            new WritablePaths([
                $this->basePath,
                $this->getAssetPath(),
                $this->storagePath,
            ])
        );
    }

    public function build(): Pipeline
    {
        $pipeline = new Pipeline;

        // A new array to persist some objects between steps.
        // It's an instance variable so that access in closures is easier. :)
        $this->tmp = [];

        $pipeline->pipe(function () {
            return new BuildConfig(
                $this->debug, $this->dbConfig, $this->baseUrl,
                function ($config) {
                    $this->tmp['config'] = $config;
                }
            );
        });

        $pipeline->pipe(function () {
            return new ConnectToDatabase(
                $this->dbConfig,
                function ($connection) {
                    $this->tmp['db'] = $connection;
                }
            );
        });

        $pipeline->pipe(function () {
            return new StoreConfig($this->tmp['config'], $this->getConfigPath());
        });

        $pipeline->pipe(function () {
            return new RunMigrations($this->tmp['db'], $this->getMigrationPath());
        });

        $pipeline->pipe(function () {
            return new WriteSettings($this->tmp['db'], $this->defaultSettings);
        });

        $pipeline->pipe(function () {
            return new CreateAdminUser($this->tmp['db'], $this->adminUser);
        });

        $pipeline->pipe(function () {
            return new PublishAssets($this->basePath, $this->getAssetPath());
        });

        $pipeline->pipe(function () {
            return new EnableBundledExtensions($this->tmp['db'], $this->basePath, $this->getAssetPath());
        });

        return $pipeline;
    }

    private function getConfigPath()
    {
        return $this->basePath.'/'.($this->configPath ?? 'config.php');
    }

    private function getAssetPath()
    {
        return "$this->publicPath/assets";
    }

    private function getMigrationPath()
    {
        return __DIR__.'/../../migrations';
    }
}
