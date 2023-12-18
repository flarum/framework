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
        ->get('/package-manager-tasks', 'package-manager.tasks.index', Api\Controller\ListTasksController::class)
        ->post('/package-manager/composer', 'package-manager.composer', Api\Controller\ConfigureComposerController::class),

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
        ->default(Settings\LastUpdateCheck::key(), json_encode(Settings\LastUpdateCheck::default()))
        ->default(Settings\LastUpdateRun::key(), json_encode(Settings\LastUpdateRun::default()))
        ->default('flarum-package-manager.queue_jobs', false)
        ->default('flarum-package-manager.minimum_stability', 'stable')
        ->default('flarum-package-manager.task_retention_days', 7),

    (new Extend\ServiceProvider)
        ->register(PackageManagerServiceProvider::class),

    (new Extend\ErrorHandling)
        ->handler(Exception\ComposerCommandFailedException::class, Exception\ExceptionHandler::class)
        ->handler(Exception\ComposerRequireFailedException::class, Exception\ExceptionHandler::class)
        ->handler(Exception\ComposerUpdateFailedException::class, Exception\ExceptionHandler::class)
        ->handler(Exception\MajorUpdateFailedException::class, Exception\ExceptionHandler::class)
        ->status('extension_already_installed', 409)
        ->status('extension_not_installed', 409)
        ->status('no_new_major_version', 409)
        ->status('extension_not_directly_dependency', 409),
];
