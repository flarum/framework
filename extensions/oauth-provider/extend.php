<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider;

use Flarum\Audit\Extend\Audit;
use Flarum\Extend;
use Flarum\Gdpr\Extend\UserData as GdprUserData;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\User\Event\LoggedOut;
use Flarum\User\Search\UserSearcher;
use Illuminate\Console\Scheduling\Event;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/resources/less/admin.less')
        ->jsDirectory(__DIR__.'/js/dist/admin'),

    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/resources/less/forum.less'),

    new Extend\Locales(__DIR__.'/resources/locale'),

    (new Extend\Routes('forum'))
        ->get('/oauth/authorize', 'oauthProvider.authorize', Http\Controller\AuthorizeController::class)
        ->post('/oauth/authorize', 'oauthProvider.authorize.post', Http\Controller\AuthorizeController::class)
        ->post('/oauth/token', 'oauthProvider.token', Http\Controller\TokenController::class)
        ->get('/oauth/userinfo', 'oauthProvider.userinfo', Http\Controller\UserInfoController::class)
        ->post('/oauth/revoke', 'oauthProvider.revoke', Http\Controller\RevokeController::class)
        ->post('/oauth/register', 'oauthProvider.register', Http\Controller\RegisterClientController::class)
        ->get('/.well-known/openid-configuration', 'oauthProvider.discovery', Http\Controller\OpenIdConfigurationController::class)
        ->get('/.well-known/oauth-authorization-server', 'oauthProvider.metadata', Http\Controller\AuthorizationServerMetadataController::class)
        ->get('/.well-known/jwks.json', 'oauthProvider.jwks', Http\Controller\JwksController::class),

    // Called by OAuth clients without a session; they authenticate as clients, not users.
    (new Extend\Csrf())
        ->exemptRoute('oauthProvider.token')
        ->exemptRoute('oauthProvider.revoke')
        ->exemptRoute('oauthProvider.register'),

    new Extend\ApiResource(Api\Resource\ClientResource::class),
    new Extend\ApiResource(Api\Resource\ConsentResource::class),

    (new Extend\SearchDriver(DatabaseSearchDriver::class))
        ->addFilter(UserSearcher::class, Search\ConnectedClientFilter::class),

    (new Extend\Settings())
        ->default(ClientRegistration\AllowedClients::SELF_REGISTERED_SETTING, true)
        ->default(ClientRegistration\AllowedClients::ADMIN_SETTING, true),

    (new Extend\Conditional())
        // The applications a member connected are personal data: exported
        // with the member, disconnected when they are anonymised or erased.
        ->whenExtensionEnabled('flarum-gdpr', fn () => [
            (new GdprUserData())
                ->addType(Data\ConnectedApplications::class),
        ])
        ->whenExtensionEnabled('flarum-audit', fn () => [
            (new Audit())
                ->group('flarum-oauth-provider')
                ->using(new AuditIntegration()),
        ]),

    (new Extend\Event())
        ->listen(LoggedOut::class, Listener\DisconnectApplicationsOnGlobalLogout::class),

    (new Extend\View())
        ->namespace('flarum-oauth-provider', __DIR__.'/resources/views'),

    (new Extend\ServiceProvider())
        ->register(Provider\OAuthProviderServiceProvider::class),

    (new Extend\Console())
        ->command(Console\PruneClientsCommand::class)
        ->schedule(Console\PruneClientsCommand::class, function (Event $event): void {
            $event->daily();
        }),
];
