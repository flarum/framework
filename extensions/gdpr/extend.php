<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr;

use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Extend;
use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\User\User;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/resources/less/admin.less'),

    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/resources/less/forum.less'),

    new Extend\Locales(__DIR__.'/resources/locale'),

    (new Extend\Routes('forum'))
        ->get('/gdpr/export/{file}', 'gdpr.export', Http\Controller\ExportController::class)
        ->get('/gdpr/erasure/confirm/{token}', 'gdpr.erasure.confirm', Http\Controller\ConfirmErasureController::class),

    (new Extend\Notification())
        ->type(Notifications\ExportAvailableBlueprint::class, ['alert', 'email'])
        ->type(Notifications\ConfirmErasureBlueprint::class, ['email'])
        ->type(Notifications\ErasureRequestCancelledBlueprint::class, ['alert', 'email']),

    (new Extend\Model(User::class))
        ->cast('anonymized', 'boolean')
        ->hasOne('erasureRequest', ErasureRequest::class),

    new Extend\ApiResource(Api\Resource\DataTypeResource::class),
    new Extend\ApiResource(Api\Resource\ExportResource::class),
    new Extend\ApiResource(Api\Resource\ErasureRequestResource::class),

    (new Extend\ApiResource(Resource\UserResource::class))
        ->endpoint(Endpoint\Show::class, function (Endpoint\Show $endpoint) {
            return $endpoint->addDefaultInclude(['erasureRequest']);
        })
        ->endpoint(Endpoint\Delete::class, Api\UserResourceDeleteEndpoint::class)
        ->fields(Api\UserResourceFields::class),

    (new Extend\ApiResource(Resource\ForumResource::class))
        ->fields(Api\ForumResourceFields::class)
        ->endpoint(Endpoint\Show::class, function (Endpoint\Show $endpoint) {
            return $endpoint->addDefaultInclude(['actor.erasureRequest']);
        }),

    (new Extend\Settings())
        ->default('flarum-gdpr.allow-anonymization', true)
        ->default('flarum-gdpr.allow-deletion', false)
        ->default('flarum-gdpr.default-anonymous-username', 'Anonymous')
        ->default('flarum-gdpr.default-erasure', ErasureRequest::MODE_ANONYMIZATION)
        ->serializeToForum('erasureAnonymizationAllowed', 'flarum-gdpr.allow-anonymization', 'boolVal')
        ->serializeToForum('erasureDeletionAllowed', 'flarum-gdpr.allow-deletion', 'boolVal'),

    (new Extend\View())
        ->namespace('flarum-gdpr', __DIR__.'/resources/views'),

    (new Extend\Console())
        ->command(Console\DestroyExportsCommand::class)
        ->command(Console\ProcessEraseRequests::class)
        ->schedule(Console\ProcessEraseRequests::class, Console\DailySchedule::class)
        ->schedule(Console\DestroyExportsCommand::class, Console\DailySchedule::class),

    (new Extend\ServiceProvider())
        ->register(Providers\GdprProvider::class),

    (new Extend\Filesystem())
        ->disk('gdpr-export', ExportDiskConfig::class),

    (new Extend\Policy())
        ->modelPolicy(User::class, Access\UserPolicy::class)
        ->modelPolicy(ErasureRequest::class, Access\ErasureRequestPolicy::class),
];
