<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

/*
 * 🚨 Honours Opcache::requestWebReset() before ANY extension boots.
 *
 * Composer runs this while vendor/autoload.php loads. The service provider was
 * too late: Flarum registers every extension's providers before booting any,
 * so a stale compiled extend.php naming a class its new files deleted killed
 * each request before the reset that would fix it could run (fbsfb.com,
 * 2026-10-15). One stat per web request; storage at Flarum's default place
 * beside vendor/, and the service provider covers a site that moved it.
 */
if (PHP_SAPI !== 'cli') {
    // The newest loader is this site's: keyed by its vendor dir, symlinks intact.
    define('FLARUM_EXTENSION_MANAGER_EARLY_STORAGE', dirname((string) array_key_first(Composer\Autoload\ClassLoader::getRegisteredLoaders())).'/storage');

    if (is_file(Flarum\ExtensionManager\Host\StorageDir::of(FLARUM_EXTENSION_MANAGER_EARLY_STORAGE).'/opcache-reset')) {
        Flarum\ExtensionManager\Host\Opcache::honourWebReset(FLARUM_EXTENSION_MANAGER_EARLY_STORAGE);
    }
}
