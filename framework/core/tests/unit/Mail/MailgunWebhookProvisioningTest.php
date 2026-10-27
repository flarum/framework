<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Mail;

use Flarum\Mail\MailgunDriver;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\unit\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Response;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\RequestInterface;

class MailgunWebhookProvisioningTest extends TestCase
{
    private const OURS = 'https://forum.example.com/mail/webhook/mailgun';
    private const OTHER = 'https://other-app.example.com/hooks/mailgun';

    /** @var array<int, array{request: RequestInterface}> */
    private array $history = [];

    private function driver(Response ...$responses): MailgunDriver
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        return new class($stack) extends MailgunDriver {
            public function __construct(private HandlerStack $stack)
            {
            }

            protected function apiClient(SettingsRepositoryInterface $settings): Client
            {
                return new Client([
                    'handler' => $this->stack,
                    'base_uri' => 'https://api.mailgun.net/v3/domains/mg.example.com/',
                ]);
            }
        };
    }

    private function settings(): SettingsRepositoryInterface
    {
        $settings = m::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')->andReturn(null);

        return $settings;
    }

    private static function urls(string ...$urls): Response
    {
        return new Response(200, [], json_encode(['webhook' => ['urls' => $urls]]));
    }

    /**
     * @return list<string>
     */
    private function requests(): array
    {
        return array_map(
            fn (array $entry) => $entry['request']->getMethod().' '.$entry['request']->getUri()->getPath(),
            $this->history
        );
    }

    /**
     * @return list<string>
     */
    private function sentUrls(int $index): array
    {
        return (array) (Query::parse((string) $this->history[$index]['request']->getBody())['url'] ?? []);
    }

    #[Test]
    public function registering_keeps_urls_another_app_registered_for_the_same_event(): void
    {
        $this->driver(
            self::urls(self::OTHER),
            new Response(200),
            self::urls(self::OTHER),
            new Response(200),
        )->registerWebhook($this->settings(), self::OURS);

        $this->assertSame([
            'GET /v3/domains/mg.example.com/webhooks/permanent_fail',
            'PUT /v3/domains/mg.example.com/webhooks/permanent_fail',
            'GET /v3/domains/mg.example.com/webhooks/complained',
            'PUT /v3/domains/mg.example.com/webhooks/complained',
        ], $this->requests());
        $this->assertSame([self::OTHER, self::OURS], $this->sentUrls(1));
        $this->assertSame([self::OTHER, self::OURS], $this->sentUrls(3));
    }

    #[Test]
    public function registering_creates_the_webhook_when_the_event_has_none(): void
    {
        $this->driver(
            new Response(404),
            new Response(200),
            new Response(404),
            new Response(200),
        )->registerWebhook($this->settings(), self::OURS);

        $this->assertSame([
            'GET /v3/domains/mg.example.com/webhooks/permanent_fail',
            'POST /v3/domains/mg.example.com/webhooks',
            'GET /v3/domains/mg.example.com/webhooks/complained',
            'POST /v3/domains/mg.example.com/webhooks',
        ], $this->requests());
        $this->assertSame(['id' => 'permanent_fail', 'url' => self::OURS], Query::parse((string) $this->history[1]['request']->getBody()));
        $this->assertSame(['id' => 'complained', 'url' => self::OURS], Query::parse((string) $this->history[3]['request']->getBody()));
    }

    #[Test]
    public function registering_again_changes_nothing(): void
    {
        $this->driver(
            self::urls(self::OTHER, self::OURS),
            self::urls(self::OURS),
        )->registerWebhook($this->settings(), self::OURS);

        $this->assertSame([
            'GET /v3/domains/mg.example.com/webhooks/permanent_fail',
            'GET /v3/domains/mg.example.com/webhooks/complained',
        ], $this->requests());
    }

    #[Test]
    public function registering_is_refused_when_the_event_already_has_three_other_urls(): void
    {
        $driver = $this->driver(
            self::urls('https://a.example/1', 'https://b.example/2', 'https://c.example/3'),
            new Response(200),
            new Response(404),
            new Response(200),
        );

        $this->expectException(\RuntimeException::class);

        try {
            $driver->registerWebhook($this->settings(), self::OURS);
        } finally {
            $this->assertSame(['GET /v3/domains/mg.example.com/webhooks/permanent_fail'], $this->requests());
        }
    }

    #[Test]
    public function registering_with_a_rejected_api_key_fails_without_writing(): void
    {
        $driver = $this->driver(
            new Response(401, [], 'Forbidden'),
            new Response(200),
            new Response(404),
            new Response(200),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HTTP 401');

        try {
            $driver->registerWebhook($this->settings(), self::OURS);
        } finally {
            $this->assertSame(['GET /v3/domains/mg.example.com/webhooks/permanent_fail'], $this->requests());
        }
    }

    #[Test]
    public function unregistering_leaves_urls_another_app_registered(): void
    {
        $this->driver(
            self::urls(self::OTHER, self::OURS),
            new Response(200),
            self::urls(self::OURS, self::OTHER),
            new Response(200),
        )->unregisterWebhook($this->settings(), self::OURS);

        $this->assertSame([
            'GET /v3/domains/mg.example.com/webhooks/permanent_fail',
            'PUT /v3/domains/mg.example.com/webhooks/permanent_fail',
            'GET /v3/domains/mg.example.com/webhooks/complained',
            'PUT /v3/domains/mg.example.com/webhooks/complained',
        ], $this->requests());
        $this->assertSame([self::OTHER], $this->sentUrls(1));
        $this->assertSame([self::OTHER], $this->sentUrls(3));
    }

    #[Test]
    public function unregistering_deletes_the_webhook_when_ours_was_its_only_url(): void
    {
        $this->driver(
            self::urls(self::OURS),
            new Response(200),
            self::urls(self::OURS),
            new Response(200),
        )->unregisterWebhook($this->settings(), self::OURS);

        $this->assertSame([
            'GET /v3/domains/mg.example.com/webhooks/permanent_fail',
            'DELETE /v3/domains/mg.example.com/webhooks/permanent_fail',
            'GET /v3/domains/mg.example.com/webhooks/complained',
            'DELETE /v3/domains/mg.example.com/webhooks/complained',
        ], $this->requests());
    }

    #[Test]
    public function unregistering_a_url_that_is_not_registered_changes_nothing(): void
    {
        $this->driver(
            self::urls(self::OTHER),
            new Response(404),
            new Response(200),
            new Response(200),
        )->unregisterWebhook($this->settings(), self::OURS);

        $this->assertSame([
            'GET /v3/domains/mg.example.com/webhooks/permanent_fail',
            'GET /v3/domains/mg.example.com/webhooks/complained',
        ], $this->requests());
    }
}
