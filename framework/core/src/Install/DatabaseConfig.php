<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install;

use Illuminate\Contracts\Support\Arrayable;

class DatabaseConfig implements Arrayable
{
    public function __construct(
        private readonly string $driver,
        private readonly string $host,
        private readonly int $port,
        private readonly string $database,
        private readonly string $username,
        private readonly string $password,
        private readonly string $prefix
    ) {
        $this->validate();
    }

    public function toArray(): array
    {
        return [
            'driver' => $this->driver,
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => $this->prefix,
            'strict' => false,
            'engine' => 'InnoDB',
            'prefix_indexes' => true
        ];
    }

    private function validate(): void
    {
        if (empty($this->driver)) {
            throw new ValidationFailed('Please specify a database driver.');
        }

        if ($this->driver !== 'mysql') {
            throw new ValidationFailed('Currently, only MySQL/MariaDB is supported.');
        }

        if (empty($this->host)) {
            throw new ValidationFailed('Please specify the hostname of your database server.');
        }

        if ($this->port < 1 || $this->port > 65535) {
            throw new ValidationFailed('Please provide a valid port number between 1 and 65535.');
        }

        if (empty($this->database)) {
            throw new ValidationFailed('Please specify the database name.');
        }

        if (empty($this->username)) {
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
}
