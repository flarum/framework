<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install;

use Flarum\Foundation\Paths;
use Illuminate\Database\ConnectionInterface;

class Installation
{
    private string $configPath;
    private bool $debug = false;
    private BaseUrl $baseUrl;
    private array $customSettings = [];
    private ?array $enabledExtensions = null;
    private DatabaseConfig $dbConfig;
    private AdminUser $adminUser;
    private ?string $accessToken = null;

    // A few instance variables to persist objects between steps.
    // Could also be local variables in build(), but this way
    // access in closures is easier. :)
    private ConnectionInterface $db;

    public function __construct(
        private readonly Paths $paths
    ) {
    }

    public function configPath(string $path): self
    {
        $this->configPath = $path;

        return $this;
    }

    public function debugMode(bool $flag): self
    {
        $this->debug = $flag;

        return $this;
    }

    public function databaseConfig(DatabaseConfig $dbConfig): self
    {
        $this->dbConfig = $dbConfig;

        return $this;
    }

    public function baseUrl(BaseUrl $baseUrl): self
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    public function settings(array $settings): self
    {
        $this->customSettings = $settings;

        return $this;
    }

    public function extensions(?array $enabledExtensions): self
    {
        $this->enabledExtensions = $enabledExtensions;

        return $this;
    }

    public function adminUser(AdminUser $admin): self
    {
        $this->adminUser = $admin;

        return $this;
    }

    public function accessToken(string $token): self
    {
        $this->accessToken = $token;

        return $this;
    }

    public function prerequisites(): Prerequisite\PrerequisiteInterface
    {
        return new Prerequisite\Composite(
            new Prerequisite\PhpVersion('8.1.0'),
            new Prerequisite\PhpExtensions([
                'dom',
                'fileinfo',
                'gd',
                'json',
                'mbstring',
                'openssl',
                'pdo_mysql',
                'tokenizer',
                'zip',
            ]),
            new Prerequisite\WritablePaths([
                $this->paths->base,
                $this->getAssetPath().'/*',
                $this->paths->storage,
            ])
        );
    }

    public function build(): Pipeline
    {
        $pipeline = new Pipeline;

        $pipeline->pipe(function () {
            return new Steps\ConnectToDatabase(
                $this->dbConfig,
                function ($connection) {
                    $this->db = $connection;
                }
            );
        });

        $pipeline->pipe(function () {
            return new Steps\StoreConfig(
                $this->debug,
                $this->dbConfig,
                $this->baseUrl,
                $this->getConfigPath()
            );
        });

        $pipeline->pipe(function () {
            return new Steps\RunMigrations($this->db, $this->getMigrationPath());
        });

        $pipeline->pipe(function () {
            return new Steps\WriteSettings($this->db, $this->customSettings);
        });

        $pipeline->pipe(function () {
            return new Steps\CreateAdminUser($this->db, $this->adminUser, $this->accessToken);
        });

        $pipeline->pipe(function () {
            return new Steps\PublishAssets($this->paths->vendor, $this->getAssetPath());
        });

        $pipeline->pipe(function () {
            return new Steps\EnableBundledExtensions($this->db, $this->paths->vendor, $this->getAssetPath(), $this->enabledExtensions);
        });

        return $pipeline;
    }

    private function getConfigPath(): string
    {
        return $this->paths->base.'/'.($this->configPath ?? 'config.php');
    }

    private function getAssetPath(): string
    {
        return $this->paths->public.'/assets';
    }

    private function getMigrationPath(): string
    {
        return __DIR__.'/../../migrations';
    }
}
