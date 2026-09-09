<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Server\Repository;

use Carbon\Carbon;
use Flarum\OAuthProvider\Models\RefreshToken;
use Flarum\OAuthProvider\Server\Entity\RefreshTokenEntity;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function getNewRefreshToken(): ?RefreshTokenEntityInterface
    {
        return new RefreshTokenEntity();
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        if (RefreshToken::query()->where('id', $refreshTokenEntity->getIdentifier())->exists()) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }

        RefreshToken::query()->create([
            'id' => $refreshTokenEntity->getIdentifier(),
            'access_token_id' => $refreshTokenEntity->getAccessToken()->getIdentifier(),
            'revoked' => false,
            'expires_at' => Carbon::instance($refreshTokenEntity->getExpiryDateTime()),
            'created_at' => Carbon::now(),
        ]);
    }

    public function revokeRefreshToken($tokenId): void
    {
        RefreshToken::query()->where('id', $tokenId)->update(['revoked' => true]);
    }

    public function isRefreshTokenRevoked($tokenId): bool
    {
        /** @var RefreshToken|null $token */
        $token = RefreshToken::query()->where('id', $tokenId)->first();

        if ($token === null) {
            return true;
        }

        return (bool) $token->revoked;
    }
}
