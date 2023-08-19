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
use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Str;
use RangeException;

class ConnectToDatabase implements Step
{
    public function __construct(
        private readonly DatabaseConfig $dbConfig,
        private readonly Closure $store
    ) {
    }

    public function getMessage(): string
    {
        return 'Connecting to database';
    }

    public function run(): void
    {
        $config = $this->dbConfig->toArray();
        $pdo = (new MySqlConnector)->connect($config);

        $version = $pdo->query('SELECT VERSION()')->fetchColumn();

        if (Str::contains($version, 'MariaDB')) {
            if (version_compare($version, '10.0.5', '<')) {
                throw new RangeException('MariaDB version too low. You need at least MariaDB 10.0.5');
            }
        } else {
            if (version_compare($version, '5.6.0', '<')) {
                throw new RangeException('MySQL version too low. You need at least MySQL 5.6.');
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
}
