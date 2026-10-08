<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\integration\api;

use Carbon\Carbon;
use Flarum\OAuthProvider\Models\Client;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class ClientCrudTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-oauth-provider');

        $now = Carbon::now()->toDateTimeString();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            'oauth_provider_clients' => [
                [
                    'id' => 'existing-client',
                    'name' => 'Existing',
                    'secret' => null,
                    'redirect_uris' => json_encode(['https://example.com/cb']),
                    'scopes' => null,
                    'confidential' => 1,
                    'revoked' => 0,
                    'created_at' => $now,
                ],
                [
                    'id' => 'client-to-update',
                    'name' => 'Old Name',
                    'secret' => null,
                    'redirect_uris' => json_encode(['https://example.com/cb']),
                    'scopes' => null,
                    'confidential' => 1,
                    'revoked' => 0,
                    'created_at' => $now,
                ],
                [
                    'id' => 'client-to-rotate',
                    'name' => 'Rotate Me',
                    'secret' => hash('sha256', 'original-secret'),
                    'redirect_uris' => json_encode(['https://example.com/cb']),
                    'scopes' => null,
                    'confidential' => 1,
                    'revoked' => 0,
                    'created_at' => $now,
                ],
                [
                    'id' => 'public-client',
                    'name' => 'Public',
                    'secret' => null,
                    'redirect_uris' => json_encode(['https://example.com/cb']),
                    'scopes' => null,
                    'confidential' => 0,
                    'revoked' => 0,
                    'created_at' => $now,
                ],
                [
                    'id' => 'protected-client',
                    'name' => 'Protected',
                    'secret' => hash('sha256', 'x'),
                    'redirect_uris' => json_encode(['https://example.com/cb']),
                    'scopes' => null,
                    'confidential' => 1,
                    'revoked' => 0,
                    'created_at' => $now,
                ],
                [
                    'id' => 'client-to-delete',
                    'name' => 'Delete Me',
                    'secret' => null,
                    'redirect_uris' => json_encode(['https://example.com/cb']),
                    'scopes' => null,
                    'confidential' => 1,
                    'revoked' => 0,
                    'created_at' => $now,
                ],
            ],
        ]);
    }

    #[Test]
    public function guest_cannot_list_clients(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/oauth-provider-clients')
                ->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(401, $response->getStatusCode());
    }

    #[Test]
    public function normal_user_cannot_list_clients(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/oauth-provider-clients', ['authenticatedAs' => 2])
                ->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function admin_can_list_clients(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/oauth-provider-clients', ['authenticatedAs' => 1])
                ->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertGreaterThanOrEqual(1, count($body['data']));

        $names = array_map(fn ($row) => $row['attributes']['name'], $body['data']);
        $this->assertContains('Existing', $names);
    }

    #[Test]
    public function admin_can_create_confidential_client_and_receives_plain_secret(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/oauth-provider-clients', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'oauth-provider-clients',
                        'attributes' => [
                            'name' => 'Test App',
                            'redirectUris' => ['https://testapp.example.com/callback'],
                            'scopes' => ['openid', 'profile'],
                            'confidential' => true,
                        ],
                    ],
                ],
            ])->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(201, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertNotEmpty($body['data']['id']);
        $this->assertEquals('Test App', $body['data']['attributes']['name']);
        $this->assertTrue($body['data']['attributes']['confidential']);
        $this->assertIsString($body['data']['attributes']['plainSecret']);
        $this->assertNotEmpty($body['data']['attributes']['plainSecret']);

        /** @var Client $client */
        $client = Client::query()->find($body['data']['id']);
        $this->assertNotNull($client);

        // Secret stored on the model is a sha256 hash of the plain secret we returned.
        $this->assertEquals(
            hash('sha256', $body['data']['attributes']['plainSecret']),
            $client->secret
        );
    }

    #[Test]
    public function public_client_has_no_secret(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/oauth-provider-clients', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'oauth-provider-clients',
                        'attributes' => [
                            'name' => 'Public App',
                            'redirectUris' => ['https://spa.example.com/callback'],
                            'confidential' => false,
                        ],
                    ],
                ],
            ])->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(201, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertFalse($body['data']['attributes']['confidential']);
        $this->assertNull($body['data']['attributes']['plainSecret']);

        /** @var Client $client */
        $client = Client::query()->find($body['data']['id']);
        $this->assertNull($client->secret);
    }

    #[Test]
    public function admin_can_update_client(): void
    {
        $response = $this->send(
            $this->request('PATCH', '/api/oauth-provider-clients/client-to-update', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'oauth-provider-clients',
                        'id' => 'client-to-update',
                        'attributes' => [
                            'name' => 'New Name',
                            'revoked' => true,
                        ],
                    ],
                ],
            ])->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(200, $response->getStatusCode());

        /** @var Client $client */
        $client = Client::query()->find('client-to-update');
        $this->assertEquals('New Name', $client->name);
        $this->assertTrue($client->revoked);
    }

    #[Test]
    public function admin_can_rotate_client_secret(): void
    {
        $oldHash = hash('sha256', 'original-secret');

        $response = $this->send(
            $this->request('POST', '/api/oauth-provider-clients/client-to-rotate/rotate-secret', [
                'authenticatedAs' => 1,
            ])->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertIsString($body['data']['attributes']['plainSecret']);
        $this->assertNotEmpty($body['data']['attributes']['plainSecret']);

        /** @var Client $client */
        $client = Client::query()->find('client-to-rotate');
        $this->assertNotEquals($oldHash, $client->secret);
        $this->assertEquals(
            hash('sha256', $body['data']['attributes']['plainSecret']),
            $client->secret
        );
    }

    #[Test]
    public function rotate_rejects_public_clients(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/oauth-provider-clients/public-client/rotate-secret', [
                'authenticatedAs' => 1,
            ])->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(422, $response->getStatusCode());
    }

    #[Test]
    public function normal_user_cannot_rotate_client_secret(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/oauth-provider-clients/protected-client/rotate-secret', [
                'authenticatedAs' => 2,
            ])->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function admin_can_delete_client(): void
    {
        $response = $this->send(
            $this->request('DELETE', '/api/oauth-provider-clients/client-to-delete', ['authenticatedAs' => 1])
                ->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertNull(Client::query()->find('client-to-delete'));
    }
}
