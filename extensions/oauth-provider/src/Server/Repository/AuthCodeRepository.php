<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Server\Repository;

use Carbon\Carbon;
use Flarum\OAuthProvider\Models\AuthCode;
use Flarum\OAuthProvider\Server\Entity\AuthCodeEntity;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;

class AuthCodeRepository implements AuthCodeRepositoryInterface
{
    public function getNewAuthCode(): AuthCodeEntityInterface
    {
        return new AuthCodeEntity();
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        if (AuthCode::query()->where('id', $authCodeEntity->getIdentifier())->exists()) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }

        $scopes = array_map(fn ($scope) => $scope->getIdentifier(), $authCodeEntity->getScopes());

        AuthCode::query()->create([
            'id' => $authCodeEntity->getIdentifier(),
            'client_id' => $authCodeEntity->getClient()->getIdentifier(),
            'user_id' => (int) $authCodeEntity->getUserIdentifier(),
            'scopes' => $scopes,
            'revoked' => false,
            'expires_at' => Carbon::instance($authCodeEntity->getExpiryDateTime()),
            'created_at' => Carbon::now(),
        ]);
    }

    public function revokeAuthCode($codeId): void
    {
        AuthCode::query()->where('id', $codeId)->update(['revoked' => true]);
    }

    public function isAuthCodeRevoked($codeId): bool
    {
        /** @var AuthCode|null $code */
        $code = AuthCode::query()->where('id', $codeId)->first();

        if ($code === null) {
            return true;
        }

        return (bool) $code->revoked;
    }
}
