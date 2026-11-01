<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\integration;

use Flarum\Audit\Tests\integration\InteractsWithAuditLog;
use Flarum\Extend;
use Flarum\OAuthProvider\Models\Client;
use Flarum\OAuthProvider\Models\Consent;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * With flarum/audit enabled, what members do with applications (connecting
 * one, signing in to it again, disconnecting one or all) and what admins do with
 * clients (adding, editing, deleting, rotating a secret) is logged.
 */
class AuditTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use InteractsWithAuditLog;
    use InteractsWithOAuth;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpAuditLog();

        $this->extend(
            (new Extend\Csrf())
                ->exemptRoute('oauthProvider.authorize')
                ->exemptRoute('oauthProvider.authorize.post'),
        );

        $this->extension('flarum-audit', 'flarum-oauth-provider');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            'oauth_provider_clients' => [
                $this->clientRow('public-client', false, extra: ['name' => 'Claude']),
                $this->clientRow('site-client', true, extra: ['name' => 'Sister forum']),
            ],
        ]);
    }

    /**
     * @param array<string, string> $query
     */
    protected function authorizeQuery(array $query = []): array
    {
        return [
            'client_id' => 'public-client',
            'response_type' => 'code',
            'redirect_uri' => 'https://testapp.example.com/callback',
            'scope' => 'openid',
            'state' => 'xyz',
            'code_challenge' => $this->challenge(),
            'code_challenge_method' => 'S256',
            ...$query,
        ];
    }

    protected function applicationPayload(string $clientId = 'public-client', string $name = 'Claude'): array
    {
        return ['user_id' => 2, 'client_id' => $clientId, 'client' => $name];
    }

    #[Test]
    public function connecting_an_application_is_logged(): void
    {
        $this->assertNotEmpty($this->publicClientCode('public-client'));

        $this->assertLogExists('user.oauth_connected', $this->applicationPayload(), 2);
        $this->assertLogDoesntExist('user.oauth_signed_in');
    }

    #[Test]
    public function signing_in_to_a_connected_application_is_logged(): void
    {
        $login = $this->loginNormalUser();

        // Approved once, then the approval covers the next sign-in.
        $this->redirectParams($this->authorize($this->authorizeQuery(), $login));
        $this->redirectParams($this->authorize($this->authorizeQuery(), $login, approve: null));

        $this->assertLogExists('user.oauth_connected', $this->applicationPayload(), 2);
        $this->assertLogExists('user.oauth_signed_in', $this->applicationPayload(), 2);
    }

    #[Test]
    public function declining_is_not_logged(): void
    {
        $params = $this->redirectParams($this->authorize($this->authorizeQuery(), approve: false));

        $this->assertSame('access_denied', $params['error'] ?? null);
        $this->assertLogDoesntExist('user.oauth_connected');
        $this->assertLogDoesntExist('user.oauth_signed_in');
    }

    #[Test]
    public function disconnecting_an_application_is_logged(): void
    {
        $this->publicClientCode('public-client');
        $consentId = Consent::query()->where('user_id', 2)->value('id');

        $this->sendSuccessfulRequest('DELETE', "/api/oauth-consents/$consentId", [], 204, 2);

        $this->assertLogExists('user.oauth_disconnected', $this->applicationPayload(), 2);
    }

    #[Test]
    public function disconnecting_every_application_is_logged(): void
    {
        $this->publicClientCode('public-client');

        $this->sendSuccessfulRequest('DELETE', '/api/oauth-consents', [], 204, 2);

        $this->assertLogExists('user.oauth_disconnected_all', ['user_id' => 2], 2);
    }

    #[Test]
    public function an_admin_adding_editing_and_deleting_a_client_is_logged(): void
    {
        $response = $this->sendSuccessfulRequest('POST', '/api/oauth-provider-clients', [
            'json' => ['data' => ['type' => 'oauth-provider-clients', 'attributes' => [
                'name' => 'New site',
                'redirectUris' => ['https://new.example.com/callback'],
            ]]],
        ], 201);
        $id = $this->json($response)['data']['id'];

        $this->assertLogExists('oauth_client.created', ['client_id' => $id, 'client' => 'New site']);

        $this->sendSuccessfulRequest('PATCH', "/api/oauth-provider-clients/$id", [
            'json' => ['data' => ['type' => 'oauth-provider-clients', 'id' => $id, 'attributes' => ['name' => 'Renamed site']]],
        ]);

        $this->assertLogExists('oauth_client.updated', ['client_id' => $id, 'client' => 'Renamed site']);

        $this->sendSuccessfulRequest('DELETE', "/api/oauth-provider-clients/$id", [], 204);

        $this->assertLogExists('oauth_client.deleted', ['client_id' => $id, 'client' => 'Renamed site']);
        $this->assertNull(Client::query()->find($id));
    }

    #[Test]
    public function an_admin_rotating_a_clients_secret_is_logged(): void
    {
        $this->sendSuccessfulRequest('POST', '/api/oauth-provider-clients/site-client/rotate-secret');

        $this->assertLogExists('oauth_client.secret_rotated', ['client_id' => 'site-client', 'client' => 'Sister forum']);
    }
}
