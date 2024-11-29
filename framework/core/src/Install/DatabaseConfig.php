<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install;

use Flarum\Foundation\Paths;
use Illuminate\Contracts\Support\Arrayable;

class DatabaseConfig implements Arrayable
{
    public function __construct(
        private readonly string $driver,
        private readonly ?string $host,
        private readonly int $port,
        private string $database,
        private readonly ?string $username,
        private readonly ?string $password,
        private readonly ?string $prefix
    ) {
        $this->validate();
    }

    public function toArray(): array
    {
        return array_merge([
            'driver' => $this->driver,
            'database' => $this->database,
            'prefix' => $this->prefix,
            'prefix_indexes' => true
        ], $this->driverOptions());
    }

    private function validate(): void
    {
        if (empty($this->driver)) {
            throw new ValidationFailed('Please specify a database driver.');
        }

        if (! in_array($this->driver, ['mysql', 'mariadb', 'sqlite', 'pgsql'])) {
            throw new ValidationFailed('Currently, only MySQL, MariaDB, SQLite and PostgreSQL are supported.');
        }

        if (in_array($this->driver, ['mysql', 'mariadb', 'pgsql']) && empty($this->host)) {
            throw new ValidationFailed('Please specify the hostname of your database server.');
        }

        if (in_array($this->driver, ['mysql', 'mariadb', 'pgsql']) && ($this->port < 1 || $this->port > 65535)) {
            throw new ValidationFailed('Please provide a valid port number between 1 and 65535.');
        }

        if (empty($this->database)) {
            throw new ValidationFailed('Please specify the database name.');
        }

        if (in_array($this->driver, ['mysql', 'mariadb', 'pgsql']) && empty($this->username)) {
            throw new ValidationFailed('Please specify the username for accessing the database.');
        }

        if (! empty($this->prefix)) {
            if (! preg_match('/^[\pL\pM\pN_]+$/u', $this->prefix)) {
                throw new ValidationFailed('The prefix may only contain characters and underscores.');
            }

            if (strlen($this->prefix) > 10) {
                throw new ValidationFailed('The prefix should be no longer than 10 characters.');
            }
        }
    }

    public function prepare(Paths $paths): void
    {
        if ($this->driver === 'sqlite' && ! file_exists($this->database)) {
            $this->database = str_replace('.sqlite', '', $this->database).'.sqlite';
            touch($paths->base.'/'.$this->database);
        }
    }

    private function driverOptions(): array
    {
        return match ($this->driver) {
            'mysql', 'mariadb' => [
                'host' => $this->host,
                'port' => $this->port,
                'username' => $this->username,
                'password' => $this->password,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'engine' => 'InnoDB',
                'strict' => false,
            ],
            'pgsql' => [
                'host' => $this->host,
                'port' => $this->port,
                'username' => $this->username,
                'password' => $this->password,
                'charset' => 'utf8',
                'search_path' => 'public',
                'sslmode' => 'prefer',
            ],
            'sqlite' => [
                'foreign_key_constraints' => true,
            ],
            default => []
        };
    }
}
