<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager;

use Flarum\Extend;
use Flarum\Foundation\Paths;
use Flarum\Frontend\Document;
use Flarum\PackageManager\Exception\ComposerCommandFailedException;
use Flarum\PackageManager\Exception\ComposerCommandFailedExceptionHandler;
use Flarum\PackageManager\Exception\ComposerRequireFailedException;
use Flarum\PackageManager\Exception\ComposerUpdateFailedException;
use Flarum\PackageManager\Exception\ExtensionAlreadyInstalledException;
use Flarum\PackageManager\Exception\ExtensionNotInstalledException;

return [
    (new Extend\Routes('api'))
        ->post('/package-manager/extensions', 'package-manager.extensions.require', Api\Controller\RequireExtensionController::class)
        ->patch('/package-manager/extensions/{id}', 'package-manager.extensions.update', Api\Controller\UpdateExtensionController::class)
        ->delete('/package-manager/extensions/{id}', 'package-manager.extensions.remove', Api\Controller\RemoveExtensionController::class)
        ->post('/package-manager/check-for-updates', 'package-manager.check-for-updates', Api\Controller\CheckForUpdatesController::class)
        ->post('/package-manager/minor-update', 'package-manager.minor-update', Api\Controller\MinorFlarumUpdateController::class)
        ->post('/package-manager/global-update', 'package-manager.global-update', Api\Controller\GlobalUpdateController::class),

    (new Extend\Frontend('admin'))
        ->css(__DIR__ . '/less/admin.less')
        ->js(__DIR__ . '/js/dist/admin.js')
        ->content(function (Document $document) {
            $paths = resolve(Paths::class);

            $document->payload['isRequiredDirectoriesWritable'] = is_writable($paths->vendor)
                && is_writable($paths->storage.'/.composer')
                && is_writable($paths->base.'/composer.json')
                && is_writable($paths->base.'/composer.lock');

            $document->payload['lastUpdateCheck'] = resolve(LastUpdateCheck::class)->get();
        }),

    new Extend\Locales(__DIR__ . '/locale'),

    (new Extend\ServiceProvider)
        ->register(PackageManagerServiceProvider::class),

    (new Extend\ErrorHandling)
        ->handler(ComposerCommandFailedException::class, ComposerCommandFailedExceptionHandler::class)
        ->handler(ComposerRequireFailedException::class, ComposerCommandFailedExceptionHandler::class)
        ->handler(ComposerUpdateFailedException::class, ComposerCommandFailedExceptionHandler::class)
        ->type(ExtensionAlreadyInstalledException::class, 'extension_already_installed')
        ->status('extension_already_installed', 409)
        ->type(ExtensionNotInstalledException::class, 'extension_not_installed')
        ->status('extension_not_installed', 409),
];
