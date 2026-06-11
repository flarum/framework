<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Extension\Exception\MissingDependenciesException;
use Flarum\Extension\Extension;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\Config;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;

/*
 * The audit log stores its payload in a native JSON column, which requires
 * MySQL 5.7.8+ or MariaDB 10.2.7+. Abort the migrations with a clear error if the
 * database is too old, rather than failing on a cryptic CREATE TABLE syntax error.
 *
 * The check can be bypassed by setting flarum-audit.ignore-mysql-requirement to true
 * in config.php.
 */
return [
    'up' => function (Builder $schema) {
        $config = resolve(Config::class);

        if (Arr::get($config, 'flarum-audit.ignore-mysql-requirement')) {
            return;
        }

        $version = $schema->getConnection()->selectOne('select version() as version')->version;

        if (preg_match('~^([0-9]+)\.([0-9]+)\.([0-9]+)[-.$]~', $version, $matches) !== 1) {
            // If the version format doesn't match our expectations, don't try anything.
            return;
        }

        $incompatible = false;

        // There's no easy way to know if we are using MySQL or MariaDB, so instead of a semver comparison
        // we only flag versions starting with 5.x or 10.x that don't meet the respective minor requirement.
        if ((int) $matches[1] === 5) {
            if ((int) $matches[2] < 7 || ((int) $matches[2] === 7 && (int) $matches[3] < 8)) {
                $incompatible = true;
            }
        } elseif ((int) $matches[1] === 10) {
            if ((int) $matches[2] < 2 || ((int) $matches[2] === 2 && (int) $matches[3] < 7)) {
                $incompatible = true;
            }
        }

        if (! $incompatible) {
            return;
        }

        $required = 'MySQL 5.7.8+ or MariaDB 10.2.7+';

        // The exception thrown below isn't written to the log, so we log the detail ourselves
        // since that's where we ask people to look.
        resolve(LoggerInterface::class)->error(
            'flarum-audit: Migrations aborted. Your MySQL version appears to be unsupported. '
            ."Version found: $version. Version required: $required"
        );

        $manager = resolve(ExtensionManager::class);

        // MissingDependenciesException is the only kind of exception that will be visible on the page when enabling
        // the extension. At that time we have no way to load custom javascript nor custom error handlers.
        throw new MissingDependenciesException(
            $manager->getExtension('flarum-audit'),
            [
                // Create a fake extension named after the requirement so it is surfaced through
                // ExtensionManager::pluckTitles by MissingDependenciesExceptionHandler.
                new Extension(__DIR__, [
                    'name' => 'not-an-actual-composer-package/mysql',
                    'extra' => [
                        'flarum-extension' => [
                            'title' => $required,
                        ],
                    ],
                ]),
            ]
        );
    },
    'down' => function (Builder $schema) {
        // Nothing to do.
    },
];
