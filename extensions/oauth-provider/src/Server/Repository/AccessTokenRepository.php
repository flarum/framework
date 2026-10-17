<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Server\Repository;

use Carbon\Carbon;
use Flarum\OAuthProvider\Models\AccessToken;
use Flarum\OAuthProvider\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    public function getNewToken(
        ClientEntityInterface $clientEntity,
        array $scopes,
        $userIdentifier = null
    ): AccessTokenEntityInterface {
        $token = new AccessTokenEntity();
        $token->setClient($clientEntity);

        foreach ($scopes as $scope) {
            $token->addScope($scope);
        }

        if ($userIdentifier !== null) {
            $token->setUserIdentifier($userIdentifier);
        }

        return $token;
    }

    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        if (AccessToken::query()->where('id', $accessTokenEntity->getIdentifier())->exists()) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }

        $scopes = array_map(fn ($scope) => $scope->getIdentifier(), $accessTokenEntity->getScopes());

        AccessToken::query()->create([
            'id' => $accessTokenEntity->getIdentifier(),
            'client_id' => $accessTokenEntity->getClient()->getIdentifier(),
            'user_id' => (int) $accessTokenEntity->getUserIdentifier(),
            'scopes' => $scopes,
            'revoked' => false,
            'expires_at' => Carbon::instance($accessTokenEntity->getExpiryDateTime()),
            'created_at' => Carbon::now(),
        ]);
    }

    public function revokeAccessToken($tokenId): void
    {
        AccessToken::query()->where('id', $tokenId)->update(['revoked' => true]);
    }

    public function isAccessTokenRevoked($tokenId): bool
    {
        /** @var AccessToken|null $token */
        $token = AccessToken::query()->where('id', $tokenId)->first();

        if ($token === null) {
            return true;
        }

        return (bool) $token->revoked;
    }
}
