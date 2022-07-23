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
    private $driver;
    private $host;
    private $port;
    private $database;
    private $username;
    private $password;
    private $prefix;

    public function __construct($driver, $host, $port, $database, $username, $password, $prefix)
    {
        $this->driver = $driver;
        $this->host = $host;
        $this->port = $port;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
        $this->prefix = $prefix;

        $this->validate();
    }

    public function toArray()
    {
        return [
            'driver'    => $this->driver,
            'host'      => $this->host,
            'port'      => $this->port,
            'database'  => $this->database,
            'username'  => $this->username,
            'password'  => $this->password,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => $this->prefix,
            'strict'    => false,
            'engine'    => null,
            'prefix_indexes' => true
        ];
    }

    private function validate()
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

        if (! is_int($this->port) || $this->port < 1 || $this->port > 65535) {
            throw new ValidationFailed('Please provide a valid port number between 1 and 65535.');
        }

        if (empty($this->database)) {
            throw new ValidationFailed('Please specify the database name.');
        }

        if (! is_string($this->database)) {
            throw new ValidationFailed('The database name must be a non-empty string.');
        }

        if (empty($this->username)) {
            throw new ValidationFailed('Please specify the username for accessing the database.');
        }

        if (! is_string($this->database)) {
            throw new ValidationFailed('The username must be a non-empty string.');
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
