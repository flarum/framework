<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider;

use Flarum\Extend;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/resources/less/admin.less')
        ->jsDirectory(__DIR__.'/js/dist/admin'),

    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js'),

    new Extend\Locales(__DIR__.'/resources/locale'),

    (new Extend\Routes('forum'))
        ->get('/oauth/authorize', 'oauthProvider.authorize', Http\Controller\AuthorizeController::class)
        ->post('/oauth/authorize', 'oauthProvider.authorize.post', Http\Controller\AuthorizeController::class)
        ->post('/oauth/token', 'oauthProvider.token', Http\Controller\TokenController::class)
        ->get('/oauth/userinfo', 'oauthProvider.userinfo', Http\Controller\UserInfoController::class)
        ->get('/.well-known/openid-configuration', 'oauthProvider.discovery', Http\Controller\OpenIdConfigurationController::class)
        ->get('/.well-known/jwks.json', 'oauthProvider.jwks', Http\Controller\JwksController::class),

    (new Extend\Csrf())
        ->exemptRoute('oauthProvider.token'),

    new Extend\ApiResource(Api\Resource\ClientResource::class),

    (new Extend\View())
        ->namespace('flarum-oauth-provider', __DIR__.'/resources/views'),

    (new Extend\ServiceProvider())
        ->register(Provider\OAuthProviderServiceProvider::class),
];
