<?php

/**
 *
 */

namespace SychO\PackageManager;

use Flarum\Extend;
use Flarum\Foundation\Paths;
use Illuminate\Console\Scheduling\Event;

return [
    (new Extend\Routes('api'))
        ->post('/package-manager/extensions', 'package-manager.extensions.require', Api\Controller\RequireExtensionController::class)
        ->patch('/package-manager/extensions/{id}', 'package-manager.extensions.update', Api\Controller\UpdateExtensionController::class)
        ->delete('/package-manager/extensions/{id}', 'package-manager.extensions.remove', Api\Controller\RemoveExtensionController::class),

    (new Extend\Frontend('admin'))
        ->css(__DIR__ . '/less/admin.less')
        ->js(__DIR__ . '/js/dist/admin.js'),

    new Extend\Locales(__DIR__ . '/locale'),

    (new Extend\ServiceProvider)
        ->register(ComposerEnvironmentProvider::class)
        ->register(PackageManagerServiceProvider::class),
];
