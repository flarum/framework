<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Foundation\Paths;
use Flarum\Install\AdminUser;
use Flarum\Install\BaseUrl;
use Flarum\Install\DatabaseConfig;
use Flarum\Install\Installation;

require __DIR__.'/../../vendor/autoload.php';

$host = getenv('DB_HOST') ?: 'localhost';
$port = intval(getenv('DB_PORT') ?: 3306);
$name = getenv('DB_DATABASE') ?: 'flarum_test';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';
$pref = getenv('DB_PREFIX') ?: '';

echo "Connecting to database $name at $host:$port.\n";
echo "Logging in as $user with password '$pass'.\n";
echo "Table prefix: '$pref'\n";

echo "\n\nCancel now if that's not what you want...\n";
echo "Use the following environment variables for configuration:\n";
echo "DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD, DB_PREFIX\n";

sleep(5);

echo "\nOff we go...\n";

/*
 * Setup installation configuration
 */

$installation = new Installation(
    new Paths([
        'base' => __DIR__.'/tmp',
        'public' => __DIR__.'/tmp/public',
        'storage' => __DIR__.'/tmp/storage',
        'vendor' => __DIR__.'/../../vendor',
    ])
);

$pipeline = $installation
    ->configPath('config.php')
    ->debugMode(true)
    ->baseUrl(BaseUrl::fromString('http://localhost'))
    ->databaseConfig(
        new DatabaseConfig('mysql', $host, $port, $name, $user, $pass, $pref)
    )
    ->adminUser(new AdminUser(
        'admin',
        'password',
        'admin@machine.local'
    ))
    ->settings(['mail_driver' => 'log'])
    ->build();

/*
 * Run the actual configuration
 */

$pipeline->run();

echo "Installation complete\n";
