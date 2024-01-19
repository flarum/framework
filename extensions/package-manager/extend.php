<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager;

use Flarum\Extend;
use Flarum\Foundation\Paths;
use Flarum\Frontend\Document;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\SyncQueue;

return [
    (new Extend\Routes('api'))
        ->post('/extension-manager/extensions', 'extension-manager.extensions.require', Api\Controller\RequireExtensionController::class)
        ->patch('/extension-manager/extensions/{id}', 'extension-manager.extensions.update', Api\Controller\UpdateExtensionController::class)
        ->delete('/extension-manager/extensions/{id}', 'extension-manager.extensions.remove', Api\Controller\RemoveExtensionController::class)
        ->post('/extension-manager/check-for-updates', 'extension-manager.check-for-updates', Api\Controller\CheckForUpdatesController::class)
        ->post('/extension-manager/why-not', 'extension-manager.why-not', Api\Controller\WhyNotController::class)
        ->post('/extension-manager/minor-update', 'extension-manager.minor-update', Api\Controller\MinorUpdateController::class)
        ->post('/extension-manager/major-update', 'extension-manager.major-update', Api\Controller\MajorUpdateController::class)
        ->post('/extension-manager/global-update', 'extension-manager.global-update', Api\Controller\GlobalUpdateController::class)
        ->get('/extension-manager-tasks', 'extension-manager.tasks.index', Api\Controller\ListTasksController::class)
        ->post('/extension-manager/composer', 'extension-manager.composer', Api\Controller\ConfigureComposerController::class),

    (new Extend\Frontend('admin'))
        ->css(__DIR__.'/less/admin.less')
        ->js(__DIR__.'/js/dist/admin.js')
        ->content(function (Document $document) {
            $paths = resolve(Paths::class);

            $document->payload['flarum-extension-manager.writable_dirs'] = is_writable($paths->vendor)
                && is_writable($paths->storage)
                && (! file_exists($paths->storage.'/.composer') || is_writable($paths->storage.'/.composer'))
                && is_writable($paths->base.'/composer.json')
                && is_writable($paths->base.'/composer.lock');

            $document->payload['flarum-extension-manager.using_sync_queue'] = resolve(Queue::class) instanceof SyncQueue;
        }),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Settings())
        ->default(Settings\LastUpdateCheck::key(), json_encode(Settings\LastUpdateCheck::default()))
        ->default(Settings\LastUpdateRun::key(), json_encode(Settings\LastUpdateRun::default()))
        ->default('flarum-extension-manager.queue_jobs', '0')
        ->default('flarum-extension-manager.minimum_stability', 'stable')
        ->default('flarum-extension-manager.task_retention_days', 7),

    (new Extend\ServiceProvider)
        ->register(ExtensionManagerServiceProvider::class),

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
