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
