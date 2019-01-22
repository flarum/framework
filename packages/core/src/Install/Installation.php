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

class Installation
{
    private $basePath;
    private $publicPath;
    private $storagePath;

    private $configPath;
    private $debug = false;
    private $dbConfig = [];
    private $baseUrl;
    private $customSettings = [];
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
        $this->customSettings = $settings;

        return $this;
    }

    public function adminUser($admin)
    {
        $this->adminUser = $admin;

        return $this;
    }

    public function prerequisites(): Prerequisite\PrerequisiteInterface
    {
        return new Prerequisite\Composite(
            new Prerequisite\PhpVersion('7.1.0'),
            new Prerequisite\PhpExtensions([
                'dom',
                'gd',
                'json',
                'mbstring',
                'openssl',
                'pdo_mysql',
                'tokenizer',
            ]),
            new Prerequisite\WritablePaths([
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
            return new Steps\ConnectToDatabase(
                $this->dbConfig,
                function ($connection) {
                    $this->tmp['db'] = $connection;
                }
            );
        });

        $pipeline->pipe(function () {
            return new Steps\StoreConfig(
                $this->debug, $this->dbConfig, $this->baseUrl, $this->getConfigPath()
            );
        });

        $pipeline->pipe(function () {
            return new Steps\RunMigrations($this->tmp['db'], $this->getMigrationPath());
        });

        $pipeline->pipe(function () {
            return new Steps\WriteSettings($this->tmp['db'], $this->customSettings);
        });

        $pipeline->pipe(function () {
            return new Steps\CreateAdminUser($this->tmp['db'], $this->adminUser);
        });

        $pipeline->pipe(function () {
            return new Steps\PublishAssets($this->basePath, $this->getAssetPath());
        });

        $pipeline->pipe(function () {
            return new Steps\EnableBundledExtensions($this->tmp['db'], $this->basePath, $this->getAssetPath());
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
