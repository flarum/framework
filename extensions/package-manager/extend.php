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
use Flarum\PackageManager\Exception\ComposerRequireFailedException;
use Flarum\PackageManager\Exception\ComposerUpdateFailedException;
use Flarum\PackageManager\Exception\ExceptionHandler;
use Flarum\PackageManager\Exception\MajorUpdateFailedException;
use Flarum\PackageManager\Settings\LastUpdateCheck;
use Flarum\PackageManager\Settings\LastUpdateRun;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\SyncQueue;

return [
    (new Extend\Routes('api'))
        ->post('/package-manager/extensions', 'package-manager.extensions.require', Api\Controller\RequireExtensionController::class)
        ->patch('/package-manager/extensions/{id}', 'package-manager.extensions.update', Api\Controller\UpdateExtensionController::class)
        ->delete('/package-manager/extensions/{id}', 'package-manager.extensions.remove', Api\Controller\RemoveExtensionController::class)
        ->post('/package-manager/check-for-updates', 'package-manager.check-for-updates', Api\Controller\CheckForUpdatesController::class)
        ->post('/package-manager/why-not', 'package-manager.why-not', Api\Controller\WhyNotController::class)
        ->post('/package-manager/minor-update', 'package-manager.minor-update', Api\Controller\MinorUpdateController::class)
        ->post('/package-manager/major-update', 'package-manager.major-update', Api\Controller\MajorUpdateController::class)
        ->post('/package-manager/global-update', 'package-manager.global-update', Api\Controller\GlobalUpdateController::class)
        ->get('/package-manager-tasks', 'package-manager.tasks.index', Api\Controller\ListTasksController::class),

    (new Extend\Frontend('admin'))
        ->css(__DIR__.'/less/admin.less')
        ->js(__DIR__.'/js/dist/admin.js')
        ->content(function (Document $document) {
            $paths = resolve(Paths::class);

            $document->payload['flarum-package-manager.writable_dirs'] = is_writable($paths->vendor)
                && is_writable($paths->storage)
                && (! file_exists($paths->storage.'/.composer') || is_writable($paths->storage.'/.composer'))
                && is_writable($paths->base.'/composer.json')
                && is_writable($paths->base.'/composer.lock');

            $document->payload['flarum-package-manager.using_sync_queue'] = resolve(Queue::class) instanceof SyncQueue;
        }),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Settings())
        ->default(LastUpdateCheck::key(), json_encode(LastUpdateCheck::default()))
        ->default(LastUpdateRun::key(), json_encode(LastUpdateRun::default()))
        ->default('flarum-package-manager.queue_jobs', false),

    (new Extend\ServiceProvider)
        ->register(PackageManagerServiceProvider::class),

    (new Extend\ErrorHandling)
        ->handler(ComposerCommandFailedException::class, ExceptionHandler::class)
        ->handler(ComposerRequireFailedException::class, ExceptionHandler::class)
        ->handler(ComposerUpdateFailedException::class, ExceptionHandler::class)
        ->handler(MajorUpdateFailedException::class, ExceptionHandler::class)
        ->status('extension_already_installed', 409)
        ->status('extension_not_installed', 409)
        ->status('no_new_major_version', 409),
];
