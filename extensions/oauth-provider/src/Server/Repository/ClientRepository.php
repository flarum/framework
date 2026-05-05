<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Server\Repository;

use Flarum\OAuthProvider\Models\Client;
use Flarum\OAuthProvider\Server\Entity\ClientEntity;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

class ClientRepository implements ClientRepositoryInterface
{
    public function getClientEntity($clientIdentifier): ?ClientEntityInterface
    {
        /** @var Client|null $client */
        $client = Client::query()
            ->where('id', $clientIdentifier)
            ->where('revoked', false)
            ->first();

        if ($client === null) {
            return null;
        }

        return $this->buildEntity($client);
    }

    public function validateClient($clientIdentifier, $clientSecret, $grantType): bool
    {
        /** @var Client|null $client */
        $client = Client::query()
            ->where('id', $clientIdentifier)
            ->where('revoked', false)
            ->first();

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

    private function buildEntity(Client $client): ClientEntity
    {
        $entity = new ClientEntity();
        $entity->setIdentifier($client->id);
        $entity->setName($client->name);
        $entity->setRedirectUri((array) $client->redirect_uris);
        $entity->setConfidential((bool) $client->confidential);

        return $entity;
    }
}
