<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database;

use Flarum\Extension\Extension;
use Flarum\Foundation\Application;
use Illuminate\Database\ConnectionInterface;

class MigrationSourceRepository
{
    protected $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function flarum(): array
    {
        if (! $this->databaseVersion()) {
            return $this->install();
        }

        return $this->upgrade();
    }

    public function extension(Extension $extension): ?array
    {
        if (! $extension->hasMigrations()) {
            return [];
        }

        return $extension->getMigrations();
    }

    protected function install(): array
    {
        // We read every file from the latest major/minor version migrations directory.
        // Including the create_<table>_table statements.
        $files = glob(__DIR__.'/../../migrations/'.$this->installedVersion(true).'/[0-9_]{15}_*.php');

        // Sort by timestamp.
        sort($files);

        $create = glob(__DIR__.'/../../migrations/'.$this->installedVersion(true).'/create_*.php');

        return array_merge($create, $files);
    }

    protected function upgrade(): array
    {
        $files = [];
        $add = false;

        $directories = glob(__DIR__.'/../../migrations/', GLOB_ONLYDIR);
        sort($directories, SORT_NATURAL);

        // Upgrade
        // Loop over all version migrations directory until we find the version that is currently active.
        foreach ($directories as $directory) {
            // We have found the directory matching the version database version. Start adding files.
            if (substr($directory, 1) === $this->databaseVersion(true)) {
                $add = true;
            }

            if ($add) {
                // Selectively add files, but only include those matching the format YYYY_MM_DD_HHIISS_<something>.php
                // This excludes the create_<table>_table.
                $files = array_merge($files, glob(__DIR__."/../../migrations/$directory/[0-9_]{15}_*.php"));
            }

            // Once we found the version that is installed, we can quit.
            // Theoretically this should never be necessary, it could just loop over all remaining ones.
            if (substr($directory, 1) === $this->installedVersion(true)) {
                break;
            }
        }

        // Sort by timestamp.
        sort($files);

        return $files;
    }

    protected function installedVersion(bool $short = false): string
    {
        $version = Application::VERSION;

        if ($short && $version) {
            return $this->shortVersion($version);
        }

        return $version;
    }

    protected function shortVersion(string $version): string
    {
        if (preg_match('~(?<version>^[0-9]+\.[0-9]+)~', $version, $m)) {
            return $m['version'];
        }

        return $version;
    }

    protected function databaseVersion(bool $short = false): ?string
    {
        $version = $this->connection->getSchemaBuilder()->hasTable('settings')
            ? $this->connection->table('settings')->where('key', 'version')->value('value')
            : null;

        if ($short && $version) {
            return $this->shortVersion($version);
        }

        return $version;
    }
}
