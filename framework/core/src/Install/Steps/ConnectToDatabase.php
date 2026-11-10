<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install\Steps;

use Closure;
use Flarum\Database\DatabaseRequirements;
use Flarum\Install\DatabaseConfig;
use Flarum\Install\Step;
use Illuminate\Database\Connectors\MariaDbConnector;
use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Database\Connectors\PostgresConnector;
use Illuminate\Database\Connectors\SQLiteConnector;
use Illuminate\Database\MariaDbConnection;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RangeException;

readonly class ConnectToDatabase implements Step
{
    public function __construct(
        private DatabaseConfig $dbConfig,
        private Closure $store,
        private string $basePath
    ) {
    }

    public function getMessage(): string
    {
        return 'Connecting to database';
    }

    public function run(): void
    {
        $config = $this->dbConfig->toArray();

        match ($config['driver']) {
            'mysql' => $this->mysql($config),
            'mariadb' => $this->mariadb($config),
            'pgsql' => $this->pgsql($config),
            'sqlite' => $this->sqlite($config),
            default => throw new InvalidArgumentException('Unsupported database driver: '.$config['driver']),
        };
    }

    private function mysql(array $config): void
    {
        $pdo = (new MySqlConnector)->connect($config);

        $raw = $pdo->query('SELECT VERSION()')->fetchColumn();

        if (DatabaseRequirements::isMariaDb($raw)) {
            $version = DatabaseRequirements::normaliseVersion($raw, true) ?? $raw;

            if (version_compare($version, DatabaseRequirements::MARIADB_MINIMUM, '<')) {
                throw new RangeException("MariaDB version ($version) too low. You need at least MariaDB ".DatabaseRequirements::MARIADB_MINIMUM);
            }
        } else {
            $version = DatabaseRequirements::normaliseVersion($raw, false) ?? $raw;

            if (version_compare($version, DatabaseRequirements::MYSQL_MINIMUM, '<')) {
                throw new RangeException("MySQL version ($version) too low. You need at least MySQL ".DatabaseRequirements::MYSQL_MINIMUM);
            }
        }

        ($this->store)(
            new MySqlConnection(
                $pdo,
                $config['database'],
                $config['prefix'],
                $config
            )
        );
    }

    private function mariadb(array $config): void
    {
        $pdo = (new MariaDbConnector())->connect($config);

        $raw = $pdo->query('SELECT VERSION()')->fetchColumn();
        $version = DatabaseRequirements::normaliseVersion($raw, true) ?? $raw;

        if (version_compare($version, DatabaseRequirements::MARIADB_MINIMUM, '<')) {
            throw new RangeException("MariaDB version ($version) too low. You need at least MariaDB ".DatabaseRequirements::MARIADB_MINIMUM);
        }

        ($this->store)(
            new MariaDbConnection(
                $pdo,
                $config['database'],
                $config['prefix'],
                $config
            )
        );
    }

    private function pgsql(array $config): void
    {
        $pdo = (new PostgresConnector)->connect($config);

        $version = $pdo->query('SHOW server_version')->fetchColumn();
        $version = Str::before($version, ' ');

        if (version_compare($version, DatabaseRequirements::PGSQL_MINIMUM, '<')) {
            throw new RangeException("PostgreSQL version ($version) too low. You need at least PostgreSQL 10");
        }

        ($this->store)(
            new PostgresConnection(
                $pdo,
                $config['database'],
                $config['prefix'],
                $config
            )
        );
    }

    private function sqlite(array $config): void
    {
        if (! file_exists($config['database'])) {
            $config['database'] = $this->basePath.'/'.$config['database'];
        }

        $pdo = (new SQLiteConnector())->connect($config);

        $version = $pdo->query('SELECT sqlite_version()')->fetchColumn();

        if (version_compare($version, DatabaseRequirements::SQLITE_MINIMUM, '<')) {
            throw new RangeException("SQLite version ($version) too low. You need at least SQLite ".DatabaseRequirements::SQLITE_MINIMUM);
        }

        ($this->store)(
            new SQLiteConnection(
                $pdo,
                $config['database'],
                $config['prefix'],
                $config
            )
        );
    }
}
