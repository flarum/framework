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
use Flarum\OAuthProvider\Models\Client;
use Flarum\OAuthProvider\Models\Consent;
use Flarum\OAuthProvider\Testing\InteractsWithOAuth;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

class UnverifiedClientRedirectTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use InteractsWithOAuth;

    protected const string UNVERIFIED_URI = 'https://collector.example.net/callback';
    protected const string ADMIN_URI = 'https://testapp.example.com/callback';
    protected const string DOCUMENT_CLIENT = 'https://collector.example.net/client.json';

    public function setUp(): void
    {
        parent::setUp();

        $this->extend((new Extend\Csrf())->exemptRoute('login'));

        $this->extension('flarum-suspend', 'flarum-oauth-provider');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Client::class => [
                $this->clientRow('admin-client', false),
                $this->clientRow('unverified-client', false, [self::UNVERIFIED_URI], ['source' => Client::SOURCE_DYNAMIC]),
                $this->clientRow(self::DOCUMENT_CLIENT, false, [self::UNVERIFIED_URI], [
                    'source' => Client::SOURCE_METADATA_DOCUMENT,
                    'metadata_expires_at' => Carbon::now()->addHour()->toDateTimeString(),
                ]),
            ],
        ]);
    }

    /**
     * @return array<string, array{array<string, string>, string, string}>
     */
    public static function errorsBeforeConsent(): array
    {
        return [
            'guest, prompt=none' => [['prompt' => 'none'], 'guest', 'login_required'],
            'member, prompt=none' => [['prompt' => 'none'], 'member', 'interaction_required'],
            'unknown scope' => [['scope' => 'nonsense'], 'guest', 'invalid_scope'],
            'plain PKCE' => [['code_challenge_method' => 'plain'], 'guest', 'invalid_request'],
            'unknown resource' => [['resource' => 'https://nowhere.example/'], 'guest', 'invalid_target'],
            'suspended member' => [[], 'suspended', 'access_denied'],
        ];
    }

    #[Test]
    #[DataProvider('errorsBeforeConsent')]
    public function an_unverified_client_does_not_get_errors_redirected_before_consent(array $query, string $visitor, string $error): void
    {
        $response = $this->visit('unverified-client', self::UNVERIFIED_URI, $query, $visitor);

        $this->assertSame(400, $response->getStatusCode(), $response->getHeaderLine('Location'));
        $this->assertSame('', $response->getHeaderLine('Location'));
        $this->assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
        $body = (string) $response->getBody();
        $this->assertStringContainsString('collector.example.net', $body);
        $this->assertStringContainsString($error, $body);
    }

    #[Test]
    #[DataProvider('errorsBeforeConsent')]
    public function an_admin_client_gets_errors_redirected(array $query, string $visitor, string $error): void
    {
        $response = $this->visit('admin-client', self::ADMIN_URI, $query, $visitor);

        $this->assertStringStartsWith(self::ADMIN_URI.'?', $response->getHeaderLine('Location'));
        $this->assertSame($error, $this->redirectParams($response)['error'] ?? null);
    }

    #[Test]
    public function an_unverified_client_the_member_approved_gets_errors_redirected(): void
    {
        $this->consent([self::UNVERIFIED_URI]);

        $response = $this->visit('unverified-client', self::UNVERIFIED_URI, ['prompt' => 'none', 'scope' => 'openid profile'], 'member');

        $this->assertStringStartsWith(self::UNVERIFIED_URI.'?', $response->getHeaderLine('Location'));
        $this->assertSame('interaction_required', $this->redirectParams($response)['error'] ?? null);

        $invalid = $this->visit('unverified-client', self::UNVERIFIED_URI, ['scope' => 'nonsense'], 'member');

        $this->assertSame('invalid_scope', $this->redirectParams($invalid)['error'] ?? null);
    }

    #[Test]
    public function an_approved_client_gets_access_denied_redirected_for_a_suspended_member(): void
    {
        $this->consent([self::UNVERIFIED_URI]);

        $response = $this->visit('unverified-client', self::UNVERIFIED_URI, [], 'suspended');

        $this->assertStringStartsWith(self::UNVERIFIED_URI.'?', $response->getHeaderLine('Location'));
        $this->assertSame('access_denied', $this->redirectParams($response)['error'] ?? null);
    }

    #[Test]
    public function an_approval_for_other_redirect_uris_does_not_count(): void
    {
        $this->consent(['https://elsewhere.example.org/callback']);

        $response = $this->visit('unverified-client', self::UNVERIFIED_URI, ['prompt' => 'none'], 'member');

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function a_revoked_approval_does_not_count(): void
    {
        $this->consent([self::UNVERIFIED_URI], revoked: true);

        $response = $this->visit('unverified-client', self::UNVERIFIED_URI, ['prompt' => 'none'], 'member');

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function the_error_page_escapes_what_the_client_chose(): void
    {
        $this->app();
        Client::query()->whereKey('unverified-client')->update(['name' => '<script>alert(1)</script>']);

        $response = $this->visit('unverified-client', self::UNVERIFIED_URI, ['prompt' => 'none'], 'guest');

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringNotContainsString('<script>alert(1)</script>', (string) $response->getBody());
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', (string) $response->getBody());
    }

    #[Test]
    public function a_metadata_document_client_does_not_get_errors_redirected_before_consent(): void
    {
        $response = $this->visit(self::DOCUMENT_CLIENT, self::UNVERIFIED_URI, ['prompt' => 'none'], 'guest');

        $this->assertSame(400, $response->getStatusCode(), $response->getHeaderLine('Location'));
        $this->assertSame('', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function declining_on_the_consent_form_still_redirects(): void
    {
        $query = [
            'client_id' => 'unverified-client',
            'response_type' => 'code',
            'redirect_uri' => self::UNVERIFIED_URI,
            'scope' => 'openid',
            'code_challenge' => $this->challenge(),
            'code_challenge_method' => 'S256',
        ];

        $response = $this->authorize($query, approve: false);

        $this->assertStringStartsWith(self::UNVERIFIED_URI.'?', $response->getHeaderLine('Location'));
        $this->assertSame('access_denied', $this->redirectParams($response)['error'] ?? null);
    }

    /**
     * @param list<string> $redirectUris
     */
    protected function consent(array $redirectUris, bool $revoked = false): void
    {
        $this->app();

        Consent::query()->insert([
            'user_id' => 2,
            'client_id' => 'unverified-client',
            'scopes' => json_encode(['openid']),
            'redirect_uris_hash' => Consent::hashRedirectUris($redirectUris),
            'revoked' => $revoked,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * @param array<string, string> $extra
     */
    protected function visit(string $clientId, string $redirectUri, array $extra, string $visitor): ResponseInterface
    {
        $query = [
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'scope' => 'openid',
            'code_challenge' => $this->challenge(),
            'code_challenge_method' => 'S256',
            'state' => 'xyz',
            ...$extra,
        ];

        $options = [];

        if ($visitor !== 'guest') {
            $options['cookiesFrom'] = $this->loginNormalUser();
        }

        if ($visitor === 'suspended') {
            User::query()->whereKey(2)->update(['suspended_until' => Carbon::now()->addDay()]);
        }

        return $this->send($this->request('GET', '/oauth/authorize?'.http_build_query($query), $options)->withQueryParams($query));
    }
}
