<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Server\Repository;

use Flarum\OAuthProvider\Scope\ScopeRegistry;
use Flarum\OAuthProvider\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

class ScopeRepository implements ScopeRepositoryInterface
{
    public function __construct(protected ScopeRegistry $scopes)
    {
    }

    public function getScopeEntityByIdentifier($identifier): ?ScopeEntityInterface
    {
        if (! $this->scopes->has($identifier)) {
            return null;
        }

        $entity = new ScopeEntity();
        $entity->setIdentifier($identifier);

        return $entity;
    }

    public function finalizeScopes(
        array $scopes,
        $grantType,
        ClientEntityInterface $clientEntity,
        $userIdentifier = null
    ): array {
        return $scopes;
    }
}
