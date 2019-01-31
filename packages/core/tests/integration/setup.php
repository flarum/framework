<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flarum\Install\AdminUser;
use Flarum\Install\DatabaseConfig;
use Flarum\Install\Installation;

require __DIR__.'/../../vendor/autoload.php';

$host = env('DB_HOST', 'localhost');
$port = intval(env('DB_PORT', 3306));
$name = env('DB_DATABASE', 'flarum_test');
$user = env('DB_USERNAME', 'root');
$pass = env('DB_PASSWORD', '');
$pref = env('DB_PREFIX', '');

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
    __DIR__.'/tmp',
    __DIR__.'/tmp/public',
    __DIR__.'/tmp/storage'
);

$pipeline = $installation
    ->configPath('config.php')
    ->debugMode(true)
    ->baseUrl('http://localhost')
    ->databaseConfig(new DatabaseConfig(
        'mysql',
        env('DB_HOST', 'localhost'),
        intval(env('DB_PORT', 3306)),
        env('DB_DATABASE', 'flarum_test'),
        env('DB_USERNAME', 'root'),
        env('DB_PASSWORD', ''),
        env('DB_PREFIX', '')
    ))
    ->adminUser(new AdminUser(
        'admin',
        'secret',
        'admin@flarum.email'
    ))
    ->settings(['mail_driver' => 'log'])
    ->build();

/*
 * Run the actual configuration
 */

$pipeline->run();

echo "Installation complete\n";
