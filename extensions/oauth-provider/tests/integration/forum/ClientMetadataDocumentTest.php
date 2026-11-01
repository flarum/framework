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
use Flarum\OAuthProvider\ClientRegistration\HostResolver;
use Flarum\OAuthProvider\ClientRegistration\MetadataDocumentClients;
use Flarum\OAuthProvider\Models\Client;
use Flarum\OAuthProvider\Tests\integration\CollectingLogger;
use Flarum\OAuthProvider\Tests\integration\InteractsWithOAuth;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class ClientMetadataDocumentTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use InteractsWithOAuth;

    protected const string CLIENT_ID = 'https://client.example.com/oauth/metadata.json';

    protected MockHandler $http;

    /**
     * @var list<array{request: RequestInterface}>
     */
    protected array $history = [];

    protected CollectingLogger $logger;

    /**
     * Runs before each mocked request is answered.
     *
     * @var list<callable(): void>
     */
    protected array $beforeSend = [];

    /**
     * @var array<string, list<string>>
     */
    protected array $dns = [
        'client.example.com' => ['93.184.216.34'],
        'internal.example.com' => ['192.168.1.10'],
        'private.example.com' => ['10.0.0.5'],
        'nat64.example.com' => ['64:ff9b::a9fe:a9fe'],
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->extend(
            (new Extend\Csrf())
                ->exemptRoute('login')
                ->exemptRoute('oauthProvider.authorize')
                ->exemptRoute('oauthProvider.authorize.post')
        );

        $this->extension('flarum-oauth-provider');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
        ]);

        $this->http = new MockHandler();
        $stack = HandlerStack::create($this->http);
        $stack->push(Middleware::history($this->history));
        $stack->push(Middleware::tap(function () {
            if ($callback = array_shift($this->beforeSend)) {
                $callback();
            }
        }));

        $container = $this->app()->getContainer();
        $container->instance(MetadataDocumentClients::HTTP_CLIENT, new HttpClient(['handler' => $stack]));
        $container->instance(LoggerInterface::class, $this->logger = new CollectingLogger());
        $container->instance(HostResolver::class, new class($this->dns) extends HostResolver {
            /**
             * @param array<string, list<string>> $dns
             */
            public function __construct(protected array $dns)
            {
            }

            public function resolve(string $host): array
            {
                return $this->dns[$host] ?? [];
            }
        });
    }

    /**
     * @param array<string, mixed> $overrides
     */
    protected function document(array $overrides = [], array $headers = []): Response
    {
        return new Response(200, ['Content-Type' => 'application/json; charset=utf-8', ...$headers], json_encode([
            'client_id' => self::CLIENT_ID,
            'client_name' => 'Example MCP Client',
            'redirect_uris' => ['http://127.0.0.1/callback', 'https://client.example.com/callback'],
            'token_endpoint_auth_method' => 'none',
            'logo_uri' => 'https://client.example.com/logo.png',
            'client_uri' => 'https://client.example.com',
            ...$overrides,
        ]));
    }

    protected function authorizeClient(?ResponseInterface $login = null, ?bool $approve = null, string $clientId = self::CLIENT_ID): ResponseInterface
    {
        return $this->authorize([
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => 'http://127.0.0.1:51234/callback',
            'scope' => 'openid profile',
            'state' => 'xyz',
            'code_challenge' => $this->challenge(),
            'code_challenge_method' => 'S256',
        ], $login, $approve);
    }

    protected function assertGenericFailure(ResponseInterface $response, string $loggedReason): void
    {
        $this->assertSame(400, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame([
            'error' => 'invalid_client',
            'error_description' => MetadataDocumentClients::GENERIC_ERROR,
        ], $this->json($response));
        $this->assertNotEmpty(
            array_filter($this->logger->messages, fn (string $message) => str_contains($message, $loggedReason)),
            'Expected a log line containing "'.$loggedReason.'"; got: '.implode(' | ', $this->logger->messages)
        );
    }

    #[Test]
    public function fetch_pins_the_vetted_address_bypasses_proxies_and_caps_the_body(): void
    {
        $this->http->append($this->document());

        $this->assertSame(200, $this->authorizeClient()->getStatusCode());

        $options = $this->http->getLastOptions();
        $this->assertSame([CURLOPT_RESOLVE => ['client.example.com:443:93.184.216.34']], $options['curl']);
        $this->assertSame('', $options['proxy']);
        $this->assertArrayNotHasKey('stream', $options);
        $this->assertInstanceOf(\Psr\Http\Message\StreamInterface::class, $options['sink']);
        $this->assertIsCallable($options['on_headers']);
        $this->assertGreaterThan(0, $options['timeout']);
        $this->assertLessThanOrEqual(MetadataDocumentClients::TIMEOUT, $options['timeout']);
    }

    #[Test]
    public function the_production_client_uses_the_curl_handler(): void
    {
        $container = $this->app()->getContainer();
        $container->forgetInstance(MetadataDocumentClients::HTTP_CLIENT);

        /** @var HttpClient $client */
        $client = $container->make(MetadataDocumentClients::HTTP_CLIENT);
        $handler = (new \ReflectionProperty(HandlerStack::class, 'handler'))->getValue($client->getConfig('handler'));

        $this->assertInstanceOf(\GuzzleHttp\Handler\CurlHandler::class, $handler);
    }

    #[Test]
    public function redirect_hops_share_one_time_budget(): void
    {
        $this->beforeSend[] = fn () => usleep(300000);
        $this->http->append(new Response(302, ['Location' => '/oauth/metadata.json']), $this->document());

        $this->assertSame(200, $this->authorizeClient()->getStatusCode());

        $this->assertCount(2, $this->history);
        $first = $this->history[0]['options']['timeout'];
        $second = $this->history[1]['options']['timeout'];
        $this->assertLessThanOrEqual(MetadataDocumentClients::TIMEOUT - 0.3, $second);
        $this->assertLessThan($first, $second);
    }

    #[Test]
    public function declared_oversized_document_is_refused_from_its_headers(): void
    {
        $this->http->append(new Response(200, ['Content-Type' => 'application/json', 'Content-Length' => '1000000'], '{}'));

        $this->assertGenericFailure($this->authorizeClient(), '64 KiB');
    }

    #[Test]
    public function failures_are_reported_generically_and_logged(): void
    {
        $this->http->append($this->document(['client_id' => 'https://evil.example.com/metadata.json']));

        $this->assertGenericFailure($this->authorizeClient(), 'does not match its URL');
    }

    #[Test]
    public function a_failed_client_id_is_not_fetched_again_for_a_while(): void
    {
        $this->http->append(new Response(500), $this->document());

        $login = $this->loginNormalUser();
        $this->assertSame(400, $this->authorizeClient($login)->getStatusCode());
        $this->assertSame(400, $this->authorizeClient($login)->getStatusCode());
        $this->assertCount(1, $this->history);

        Carbon::setTestNow(Carbon::now()->addSeconds(MetadataDocumentClients::FAILURE_TTL + 1));

        try {
            $this->assertSame(200, $this->authorizeClient($login)->getStatusCode());
            $this->assertCount(2, $this->history);
        } finally {
            Carbon::setTestNow();
        }
    }

    #[Test]
    public function fetches_are_rate_limited_per_address(): void
    {
        $login = $this->loginNormalUser();

        for ($i = 0; $i < MetadataDocumentClients::RATE_LIMIT; $i++) {
            $this->http->append(new Response(404));
            $this->assertSame(400, $this->authorizeClient($login, clientId: "https://client.example.com/client-$i.json")->getStatusCode());
        }

        $this->assertCount(MetadataDocumentClients::RATE_LIMIT, $this->history);

        $this->http->append($this->document());
        $this->assertGenericFailure($this->authorizeClient($login), 'rate limit');
        $this->assertCount(MetadataDocumentClients::RATE_LIMIT, $this->history);
        $this->assertNull(Client::query()->find(self::CLIENT_ID));
    }

    #[Test]
    public function nat64_address_is_never_fetched(): void
    {
        $this->assertGenericFailure($this->authorizeClient(clientId: 'https://nat64.example.com/metadata.json'), 'non-public');
        $this->assertCount(0, $this->history);
    }

    #[Test]
    public function concurrent_first_requests_both_succeed(): void
    {
        // Another request creates the row while this one is fetching.
        $this->beforeSend[] = fn () => $this->database()->table('oauth_provider_clients')->insert(
            $this->clientRow(self::CLIENT_ID, false, ['http://127.0.0.1/callback'], [
                'source' => Client::SOURCE_METADATA_DOCUMENT,
                'metadata_expires_at' => Carbon::now()->addHour()->toDateTimeString(),
            ])
        );
        $this->http->append($this->document());

        $response = $this->authorizeClient();

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame(1, Client::query()->where('id', self::CLIENT_ID)->count());
    }

    #[Test]
    public function client_name_loses_bidi_overrides(): void
    {
        $this->http->append($this->document(['client_name' => "Trusted\u{202E}ppA\u{202C} Tool"]));

        $response = $this->authorizeClient();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('TrustedppA Tool', Client::query()->findOrFail(self::CLIENT_ID)->name);
        $this->assertStringNotContainsString("\u{202E}", (string) $response->getBody());
    }

    #[Test]
    public function consent_screen_cannot_be_framed_and_names_the_redirect_host(): void
    {
        $this->http->append($this->document());

        $response = $this->authorizeClient();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
        $this->assertSame("frame-ancestors 'none'", $response->getHeaderLine('Content-Security-Policy'));
        $body = (string) $response->getBody();
        $this->assertStringContainsString('unverified, self-registered', $body);
        $this->assertStringContainsString('from client.example.com', $body);
        $this->assertStringContainsString('you will be sent to 127.0.0.1', $body);
    }

    #[Test]
    public function metadata_document_client_is_fetched_cached_and_usable(): void
    {
        $this->http->append($this->document(headers: ['Cache-Control' => 'public, max-age=60']));

        $login = $this->loginNormalUser();
        $response = $this->authorizeClient($login);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('Example MCP Client', $body);
        $this->assertStringContainsString('unverified', $body);

        $this->assertCount(1, $this->history);
        $this->assertSame('GET', $this->history[0]['request']->getMethod());
        $this->assertSame(self::CLIENT_ID, (string) $this->history[0]['request']->getUri());
        $this->assertSame('application/json', $this->history[0]['request']->getHeaderLine('Accept'));

        /** @var Client $client */
        $client = Client::query()->findOrFail(self::CLIENT_ID);
        $this->assertSame(Client::SOURCE_METADATA_DOCUMENT, $client->source);
        $this->assertFalse($client->confidential);
        $this->assertSame('https://client.example.com/logo.png', $client->logo_uri);
        $this->assertSame('client.example.com', $client->sourceHost());
        // max-age=60 is clamped up to five minutes.
        $this->assertEqualsWithDelta(Carbon::now()->addMinutes(5)->getTimestamp(), $client->metadata_expires_at->getTimestamp(), 5);

        // The approved request goes through on the cached row: no second fetch.
        $params = $this->redirectParams($this->authorizeClient($login, approve: true));
        $this->assertCount(1, $this->history);

        $response = $this->token([
            'grant_type' => 'authorization_code',
            'client_id' => self::CLIENT_ID,
            'redirect_uri' => 'http://127.0.0.1:51234/callback',
            'code' => $params['code'],
            'code_verifier' => self::VERIFIER,
        ]);
        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function metadata_document_client_is_limited_to_its_registered_scopes(): void
    {
        $this->http->append($this->document(['scope' => 'openid']));

        $this->assertSame(200, $this->authorizeClient()->getStatusCode());

        /** @var Client $client */
        $client = Client::query()->findOrFail(self::CLIENT_ID);
        $this->assertSame(['openid'], $client->scopes);
    }

    #[Test]
    public function expired_metadata_is_refetched(): void
    {
        $this->http->append($this->document(), $this->document(['client_name' => 'Renamed Client']));

        $login = $this->loginNormalUser();
        $this->assertSame(200, $this->authorizeClient($login)->getStatusCode());
        $this->assertSame(200, $this->authorizeClient($login)->getStatusCode());
        $this->assertCount(1, $this->history);

        Client::query()->where('id', self::CLIENT_ID)->update(['metadata_expires_at' => Carbon::now()->subMinute()]);

        $response = $this->authorizeClient($login);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(2, $this->history);
        $this->assertStringContainsString('Renamed Client', (string) $response->getBody());
        $this->assertSame('Renamed Client', Client::query()->findOrFail(self::CLIENT_ID)->name);
    }

    #[Test]
    public function changed_redirect_uris_ask_for_consent_again(): void
    {
        $this->http->append($this->document(), $this->document(['redirect_uris' => ['https://attacker.example.net/callback']]));

        $login = $this->loginNormalUser();
        $this->redirectParams($this->authorizeClient($login, approve: true));
        $this->assertSame(302, $this->authorizeClient($login)->getStatusCode(), 'The consent given covers the next request');

        Client::query()->where('id', self::CLIENT_ID)->update(['metadata_expires_at' => Carbon::now()->subMinute()]);

        $response = $this->authorize([
            'client_id' => self::CLIENT_ID,
            'response_type' => 'code',
            'redirect_uri' => 'https://attacker.example.net/callback',
            'scope' => 'openid profile',
            'state' => 'xyz',
            'code_challenge' => $this->challenge(),
            'code_challenge_method' => 'S256',
        ], $login, null);

        $this->assertCount(2, $this->history);
        $this->assertSame(200, $response->getStatusCode(), 'A client that changed where it sends codes must ask for consent again: '.$response->getHeaderLine('Location'));
        $this->assertStringContainsString('attacker.example.net', (string) $response->getBody());
    }

    #[Test]
    public function refetched_metadata_with_the_same_redirect_uris_keeps_the_consent(): void
    {
        $this->http->append($this->document(), $this->document([
            'client_name' => 'Renamed Client',
            'redirect_uris' => ['https://client.example.com/callback', 'http://127.0.0.1/callback'],
        ]));

        $login = $this->loginNormalUser();
        $this->redirectParams($this->authorizeClient($login, approve: true));

        Client::query()->where('id', self::CLIENT_ID)->update(['metadata_expires_at' => Carbon::now()->subMinute()]);

        $this->assertSame(302, $this->authorizeClient($login)->getStatusCode());
        $this->assertCount(2, $this->history);
    }

    #[Test]
    public function mismatched_client_id_is_refused(): void
    {
        $this->http->append($this->document(['client_id' => 'https://evil.example.com/metadata.json']));

        $response = $this->authorizeClient();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('invalid_client', $this->json($response)['error']);
        $this->assertNull(Client::query()->find(self::CLIENT_ID));
    }

    #[Test]
    public function document_with_a_client_secret_is_refused(): void
    {
        $this->http->append($this->document(['client_secret' => 'shh']));

        $this->assertSame(400, $this->authorizeClient()->getStatusCode());
        $this->assertNull(Client::query()->find(self::CLIENT_ID));
    }

    #[Test]
    public function document_with_a_non_https_redirect_uri_is_refused(): void
    {
        $this->http->append($this->document(['redirect_uris' => ['http://client.example.com/callback']]));

        $this->assertSame(400, $this->authorizeClient()->getStatusCode());
    }

    #[Test]
    public function non_json_response_is_refused(): void
    {
        $this->http->append(new Response(200, ['Content-Type' => 'text/html'], '<html></html>'));

        $this->assertSame(400, $this->authorizeClient()->getStatusCode());
    }

    #[Test]
    public function oversized_document_is_refused(): void
    {
        $this->http->append($this->document(['padding' => str_repeat('x', 70000)]));

        $this->assertSame(400, $this->authorizeClient()->getStatusCode());
    }

    #[Test]
    public function host_resolving_to_a_private_address_is_never_fetched(): void
    {
        $response = $this->authorizeClient(clientId: 'https://private.example.com/metadata.json');

        $this->assertGenericFailure($response, 'non-public');
        $this->assertCount(0, $this->history);
    }

    #[Test]
    public function literal_private_ip_is_never_fetched(): void
    {
        $this->assertSame(400, $this->authorizeClient(clientId: 'https://169.254.169.254/latest/meta-data')->getStatusCode());
        $this->assertCount(0, $this->history);
    }

    #[Test]
    public function redirect_to_a_private_address_is_not_followed(): void
    {
        $this->http->append(new Response(302, ['Location' => 'https://internal.example.com/metadata.json']));

        $this->assertSame(400, $this->authorizeClient()->getStatusCode());
        $this->assertCount(1, $this->history);
    }

    #[Test]
    public function revoked_metadata_document_client_is_not_refetched(): void
    {
        // The app is already booted (setUp binds the mocks), so prepareDatabase() would be a no-op.
        $this->database()->table('oauth_provider_clients')->insert(
            $this->clientRow(self::CLIENT_ID, false, ['http://127.0.0.1/callback'], [
                'source' => Client::SOURCE_METADATA_DOCUMENT,
                'revoked' => 1,
                'metadata_expires_at' => Carbon::now()->subDay()->toDateTimeString(),
            ])
        );

        $response = $this->authorizeClient();

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('invalid_client', $this->json($response)['error']);
        $this->assertCount(0, $this->history);
    }
}
