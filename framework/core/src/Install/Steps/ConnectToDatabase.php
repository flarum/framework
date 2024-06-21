<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install\Steps;

use Closure;
use Flarum\Install\DatabaseConfig;
use Flarum\Install\Step;
use Illuminate\Database\Connectors\MySqlConnector;
use Illuminate\Database\Connectors\SQLiteConnector;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RangeException;

class ConnectToDatabase implements Step
{
    public function __construct(
        private readonly DatabaseConfig $dbConfig,
        private readonly Closure $store,
        private readonly string $basePath
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
            'sqlite' => $this->sqlite($config),
            default => throw new InvalidArgumentException('Unsupported database driver: '.$config['driver']),
        };
    }

    private function mysql(array $config): void
    {
        $pdo = (new MySqlConnector)->connect($config);

        $version = $pdo->query('SELECT VERSION()')->fetchColumn();

        if (Str::contains($version, 'MariaDB')) {
            if (version_compare($version, '10.10.0', '<')) {
                throw new RangeException('MariaDB version too low. You need at least MariaDB 10.0.5');
            }
        } else {
            if (version_compare($version, '5.7.0', '<')) {
                throw new RangeException('MySQL version too low. You need at least MySQL 5.7');
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

    private function sqlite(array $config): void
    {
        if (! file_exists($config['database'])) {
            $config['database'] = $this->basePath.'/'.$config['database'];
        }

        $pdo = (new SQLiteConnector())->connect($config);

        $version = $pdo->query('SELECT sqlite_version()')->fetchColumn();

        if (version_compare($version, '3.8.8', '<')) {
            throw new RangeException('SQLite version too low. You need at least SQLite 3.8.8');
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
