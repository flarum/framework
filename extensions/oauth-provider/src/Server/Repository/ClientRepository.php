<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Server\Repository;

use Flarum\OAuthProvider\ClientRegistration\AllowedClients;
use Flarum\OAuthProvider\Models\Client;
use Flarum\OAuthProvider\Server\Entity\ClientEntity;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

class ClientRepository implements ClientRepositoryInterface
{
    public function __construct(
        protected AllowedClients $allowed,
    ) {
    }

    public function getClientEntity($clientIdentifier): ?ClientEntityInterface
    {
        $client = $this->find($clientIdentifier);

        if ($client === null) {
            return null;
        }

        return $this->buildEntity($client);
    }

    public function validateClient($clientIdentifier, $clientSecret, $grantType): bool
    {
        $client = $this->find($clientIdentifier);

        if ($client === null) {
            return false;
        }

        if ($client->confidential) {
            if ($clientSecret === null || $clientSecret === '') {
                return false;
            }

            return hash_equals((string) $client->secret, hash('sha256', $clientSecret));
        }

        return true;
    }

    /**
     * A client that may be used: not revoked, and of a kind the admin allows.
     */
    private function find(string $clientIdentifier): ?Client
    {
        /** @var Client|null $client */
        $client = $this->allowed->scope(Client::query())
            ->where('id', $clientIdentifier)
            ->where('revoked', false)
            ->first();

        return $client;
    }

    private function buildEntity(Client $client): ClientEntity
    {
        $entity = new ClientEntity();
        $entity->setIdentifier($client->id);
        $entity->setName($client->name);
        $entity->setRedirectUri((array) $client->redirect_uris);
        $entity->setConfidential((bool) $client->confidential);

        // Null leaves the client unrestricted; any list, the admin's
        // "Allowed scopes" included, is the most it can be granted.
        if ($client->scopes !== null) {
            $entity->setRegisteredScopes(array_values($client->scopes));
        }

        return $entity;
    }
}
