<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Server;

use DateInterval;
use Flarum\OAuthProvider\KeyManager;
use Flarum\OAuthProvider\Server\Grant\OidcAuthCodeGrant;
use Flarum\OAuthProvider\Server\Repository\AccessTokenRepository;
use Flarum\OAuthProvider\Server\Repository\AuthCodeRepository;
use Flarum\OAuthProvider\Server\Repository\ClientRepository;
use Flarum\OAuthProvider\Server\Repository\RefreshTokenRepository;
use Flarum\OAuthProvider\Server\Repository\ScopeRepository;
use Flarum\OAuthProvider\Server\ResponseType\IdTokenResponse;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResourceServer;

class AuthorizationServerFactory
{
    public function __construct(
        protected KeyManager $keys,
        protected ClientRepository $clients,
        protected AccessTokenRepository $accessTokens,
        protected RefreshTokenRepository $refreshTokens,
        protected AuthCodeRepository $authCodes,
        protected ScopeRepository $scopes,
        protected IdTokenBuilder $idTokenBuilder,
    ) {
    }

    public function authorizationServer(): AuthorizationServer
    {
        $server = new AuthorizationServer(
            $this->clients,
            $this->accessTokens,
            $this->scopes,
            $this->keys->privateKey(),
            $this->keys->encryptionKey(),
            new IdTokenResponse($this->idTokenBuilder),
        );

        $authCodeGrant = new OidcAuthCodeGrant(
            $this->authCodes,
            $this->refreshTokens,
            new DateInterval('PT10M')
        );
        $authCodeGrant->setRefreshTokenTTL(new DateInterval('P1M'));

        $server->enableGrantType($authCodeGrant, new DateInterval('PT1H'));

        $refreshGrant = new RefreshTokenGrant($this->refreshTokens);
        $refreshGrant->setRefreshTokenTTL(new DateInterval('P1M'));

        $server->enableGrantType($refreshGrant, new DateInterval('PT1H'));

        return $server;
    }

    public function resourceServer(): ResourceServer
    {
        return new ResourceServer(
            $this->accessTokens,
            $this->keys->publicKey(),
        );
    }
}
