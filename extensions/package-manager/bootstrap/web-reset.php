<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

/*
 * Runs while vendor/autoload.php loads, before any extension boots, on web
 * requests only. Two jobs, each a single cheap check when there is nothing to
 * do, and neither loads a class unless there is:
 *
 *  - Honours Opcache::requestWebReset(). The service provider was too late:
 *    Flarum registers every extension's providers before booting any, so a
 *    stale compiled extend.php naming a class its new files deleted killed each
 *    request before the reset that would fix it could run (fbsfb.com,
 *    2026-10-15). One stat per request.
 *  - Redeems the one-time token a core update sends Flarum's own updater in
 *    place of the database credentials (see Host\UpdaterToken). One isset per
 *    request.
 *
 * Storage and config.php are looked for at Flarum's default places beside
 * vendor/; the service provider covers the reset for a site that moved its
 * storage.
 */
if (PHP_SAPI !== 'cli') {
    // The newest loader is this site's: keyed by its vendor dir, symlinks intact.
    $earlyBase = dirname((string) array_key_first(Composer\Autoload\ClassLoader::getRegisteredLoaders()));
    define('FLARUM_EXTENSION_MANAGER_EARLY_STORAGE', $earlyBase.'/storage');

    if (is_file(FLARUM_EXTENSION_MANAGER_EARLY_STORAGE.'/extension-manager-opcache-reset')) {
        Flarum\ExtensionManager\Host\Opcache::honourWebReset(FLARUM_EXTENSION_MANAGER_EARLY_STORAGE);
    }

    if (isset($_SERVER['HTTP_X_EXTENSION_MANAGER_TOKEN'])) {
        Flarum\ExtensionManager\Host\UpdaterToken::honour($earlyBase, FLARUM_EXTENSION_MANAGER_EARLY_STORAGE);
    }

    unset($earlyBase);
}
