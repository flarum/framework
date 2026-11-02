<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\integration\forum;

use Carbon\Carbon;
use Flarum\Extend;
use Flarum\OAuthProvider\ClientRegistration\AllowedClients;
use Flarum\OAuthProvider\Models\Client;
use Flarum\OAuthProvider\Tests\integration\InteractsWithOAuth;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

/**
 * Self-registered applications (dynamic registration, metadata documents) and
 * the ones an admin added can each be switched off. Off, a client of that kind
 * can't authorize, refresh or use its tokens, until it is switched back on.
 */
class AllowedClientsTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use InteractsWithOAuth;

    protected const string CIMD = 'https://client.example.com/metadata.json';

    public function setUp(): void
    {
        parent::setUp();

        $this->extend(
            (new Extend\Csrf())
                ->exemptRoute('login')
                ->exemptRoute('oauthProvider.authorize')
                ->exemptRoute('oauthProvider.authorize.post'),
        );

        $this->extension('flarum-oauth-provider');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            'oauth_provider_clients' => [
                $this->clientRow('admin-client', false),
                $this->clientRow('dynamic-client', false, extra: ['source' => Client::SOURCE_DYNAMIC]),
                $this->clientRow(self::CIMD, false, extra: [
                    'source' => Client::SOURCE_METADATA_DOCUMENT,
                    'metadata_expires_at' => Carbon::now()->addDay()->toDateTimeString(),
                ]),
            ],
        ]);
    }

    protected function switchTo(string $setting, bool $on): void
    {
        $this->app()->getContainer()->make(SettingsRepositoryInterface::class)->set($setting, $on ? '1' : '0');
    }

    protected function authorizeClient(string $clientId): ResponseInterface
    {
        return $this->authorize([
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => 'https://testapp.example.com/callback',
            'scope' => 'openid',
            'state' => 'xyz',
            'code_challenge' => $this->challenge(),
            'code_challenge_method' => 'S256',
        ]);
    }

    protected function assertRefused(ResponseInterface $response): void
    {
        $this->assertGreaterThanOrEqual(400, $response->getStatusCode(), (string) $response->getBody());
        $this->assertLessThan(500, $response->getStatusCode(), (string) $response->getBody());
    }

    protected function userinfo(string $accessToken): ResponseInterface
    {
        return $this->send($this->request('GET', '/oauth/userinfo')->withHeader('Authorization', 'Bearer '.$accessToken));
    }

    /**
     * @return array<string, mixed>
     */
    protected function document(): array
    {
        return $this->json($this->send($this->request('GET', '/.well-known/oauth-authorization-server')));
    }

    #[Test]
    public function both_kinds_are_allowed_by_default(): void
    {
        $document = $this->document();

        $this->assertArrayHasKey('registration_endpoint', $document);
        $this->assertTrue($document['client_id_metadata_document_supported']);

        $this->assertNotEmpty($this->publicClientCode('admin-client'));
        $this->assertNotEmpty($this->publicClientCode('dynamic-client'));
        $this->assertNotEmpty($this->publicClientCode(self::CIMD));
    }

    #[Test]
    public function without_self_registered_clients_nothing_registers_or_is_advertised(): void
    {
        $this->setting(AllowedClients::SELF_REGISTERED_SETTING, '0');

        $document = $this->document();

        $this->assertArrayNotHasKey('registration_endpoint', $document);
        $this->assertArrayNotHasKey('client_id_metadata_document_supported', $document);

        $response = $this->send(
            $this->request('POST', '/oauth/register', ['json' => ['redirect_uris' => ['https://app.example.com/cb']]])
                ->withAttribute('bypassCsrfToken', true)
        );

        $this->assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function without_self_registered_clients_they_cannot_authorize(): void
    {
        $this->setting(AllowedClients::SELF_REGISTERED_SETTING, '0');

        $this->assertRefused($this->authorizeClient('dynamic-client'));
        $this->assertRefused($this->authorizeClient(self::CIMD));

        $this->assertNotEmpty($this->publicClientCode('admin-client'), 'Admin-added clients are unaffected');
    }

    #[Test]
    public function without_self_registered_clients_their_tokens_stop_working_until_they_are_back(): void
    {
        $tokens = $this->publicClientTokens('dynamic-client');

        $this->switchTo(AllowedClients::SELF_REGISTERED_SETTING, false);

        $this->assertSame(401, $this->userinfo($tokens['access_token'])->getStatusCode());
        $refreshed = $this->refresh('dynamic-client', $tokens['refresh_token']);
        $this->assertSame(401, $refreshed->getStatusCode(), (string) $refreshed->getBody());
        $this->assertSame('invalid_client', $this->json($refreshed)['error']);

        $this->switchTo(AllowedClients::SELF_REGISTERED_SETTING, true);

        $this->assertSame(200, $this->userinfo($tokens['access_token'])->getStatusCode());
        $this->assertSame(200, $this->refresh('dynamic-client', $tokens['refresh_token'])->getStatusCode());
    }

    #[Test]
    public function without_admin_clients_they_cannot_authorize(): void
    {
        $this->setting(AllowedClients::ADMIN_SETTING, '0');

        $this->assertRefused($this->authorizeClient('admin-client'));

        $this->assertNotEmpty($this->publicClientCode('dynamic-client'), 'Self-registered clients are unaffected');
    }

    #[Test]
    public function without_admin_clients_their_tokens_stop_working_until_they_are_back(): void
    {
        $tokens = $this->publicClientTokens('admin-client');

        $this->switchTo(AllowedClients::ADMIN_SETTING, false);

        $this->assertSame(401, $this->userinfo($tokens['access_token'])->getStatusCode());
        $refreshed = $this->refresh('admin-client', $tokens['refresh_token']);
        $this->assertSame(401, $refreshed->getStatusCode(), (string) $refreshed->getBody());
        $this->assertSame('invalid_client', $this->json($refreshed)['error']);

        $this->switchTo(AllowedClients::ADMIN_SETTING, true);

        $this->assertSame(200, $this->userinfo($tokens['access_token'])->getStatusCode());
        $this->assertSame(200, $this->refresh('admin-client', $tokens['refresh_token'])->getStatusCode());
    }
}
