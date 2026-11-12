<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\mail;

use Flarum\Extend;
use Flarum\Mail\DriverInterface;
use Flarum\Mail\HandlesWebhooks;
use Flarum\Mail\ProvisionsWebhooks;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\MessageBag;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\RemoteEvent\Event\Mailer\MailerDeliveryEvent;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Client\RequestParserInterface;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

class CustomWebhookDriverTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extend(
            (new Extend\Mail())
                ->driver('webhook-test', WebhookTestDriver::class)
        );

        $this->setting('mail_driver', 'webhook-test');

        WebhookTestDriver::$registered = [];

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
        ]);
    }

    private function postWebhook(array $payload)
    {
        return $this->send(
            $this->request('POST', '/mail/webhook/webhook-test', ['json' => $payload])
        );
    }

    private function mailSettings(): array
    {
        $response = $this->send(
            $this->request('GET', '/api/mail/settings', [
                'authenticatedAs' => 1,
            ])
        );

        return json_decode((string) $response->getBody(), true)['data']['attributes'];
    }

    #[Test]
    public function a_driver_can_turn_webhooks_off_for_its_current_settings(): void
    {
        $this->setting('webhook_test_available', '0');

        $response = $this->postWebhook(['name' => MailerDeliveryEvent::BOUNCE, 'email' => 'normal@machine.local', 'permanent' => true]);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse(User::find(2)->hasBouncedEmail());

        $attributes = $this->mailSettings();

        $this->assertFalse($attributes['canReceiveWebhooks']);
        $this->assertNull($attributes['webhookUrl']);
    }

    #[Test]
    public function a_failure_the_driver_calls_temporary_does_not_flag_the_user(): void
    {
        $response = $this->postWebhook(['name' => MailerDeliveryEvent::BOUNCE, 'email' => 'normal@machine.local', 'permanent' => false]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse(User::find(2)->hasBouncedEmail());
    }

    #[Test]
    public function a_failure_the_driver_calls_permanent_flags_the_user(): void
    {
        $response = $this->postWebhook(['name' => MailerDeliveryEvent::DROPPED, 'email' => 'normal@machine.local', 'permanent' => true]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue(User::find(2)->hasBouncedEmail());
    }

    #[Test]
    public function the_webhook_url_carries_basic_auth_credentials_for_installs_behind_basic_auth(): void
    {
        $this->setting('mail_webhook_username', 'hook');
        $this->setting('mail_webhook_password', 'p@ss:word');

        $this->assertEquals('http://hook:p%40ss%3Aword@localhost/mail/webhook/webhook-test', $this->mailSettings()['webhookUrl']);

        $response = $this->send(
            $this->request('POST', '/api/mail/webhook/provision', [
                'authenticatedAs' => 1,
                'json' => [],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals(['http://hook:p%40ss%3Aword@localhost/mail/webhook/webhook-test'], WebhookTestDriver::$registered);
    }

    #[Test]
    public function a_driver_can_obtain_its_verification_secret_during_registration(): void
    {
        $this->setting('webhook_test_configured', '0');
        $response = $this->send($this->request('POST', '/api/mail/webhook/provision', [
            'authenticatedAs' => 1, 'json' => [],
        ]));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, WebhookTestDriver::$registered);
        $this->assertTrue($this->app()->getContainer()->make(\Flarum\Mail\MailWebhooks::class)->configured());
    }

    #[Test]
    public function numeric_usernames_do_not_corrupt_the_webhook_url(): void
    {
        $this->setting('mail_webhook_username', '2hooks');
        $this->setting('mail_webhook_password', 'secret');
        $this->assertSame('http://2hooks:secret@localhost/mail/webhook/webhook-test', $this->mailSettings()['webhookUrl']);
    }

    #[Test]
    public function parsers_receive_the_external_url_through_the_real_request_pipeline(): void
    {
        $this->config('url', 'https://forum.example/community');
        $response = $this->send($this->request('POST', 'http://internal/community/mail/webhook/webhook-test?token=a%2Bb', [
            'json' => ['name' => MailerDeliveryEvent::BOUNCE, 'email' => 'normal@machine.local', 'permanent' => true,
                'expected_uri' => 'https://forum.example/community/mail/webhook/webhook-test?token=a%2Bb'],
        ]));
        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertTrue(User::find(2)->hasBouncedEmail());
    }

    #[Test]
    public function an_array_driver_is_refused_without_a_php_warning(): void
    {
        $log = $this->captureLog();
        $request = $this->request('POST', '/mail/webhook')->withQueryParams(['driver' => ['x']]);
        $response = $this->send($request);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse($log->hasErrorRecords());
    }

    private function captureLog(): TestHandler
    {
        $handler = new TestHandler();
        $this->app()->getContainer()->instance('log', new Logger('test', [$handler]));

        return $handler;
    }

    #[Test]
    public function refused_requests_cannot_inject_log_lines_or_flood_warnings(): void
    {
        $log = $this->captureLog();
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postWebhook(['reject' => "Invalid\nforged log line $i"]);
            $this->assertEquals(406, $response->getStatusCode());
        }

        $this->assertCount(1, $log->getRecords());
        $this->assertStringNotContainsString("\n", $log->getRecords()[0]->message);
    }

    #[Test]
    public function a_webhook_the_parser_rejects_is_logged_with_the_reason(): void
    {
        $log = $this->captureLog();

        $response = $this->postWebhook(['reject' => 'Signature is wrong.']);

        $this->assertEquals(406, $response->getStatusCode());
        $this->assertTrue($log->hasWarningThatContains('Signature is wrong.'));
    }

    #[Test]
    public function a_webhook_for_a_driver_that_is_not_configured_is_logged(): void
    {
        $this->setting('webhook_test_configured', '0');
        $log = $this->captureLog();

        $response = $this->postWebhook(['name' => MailerDeliveryEvent::BOUNCE, 'email' => 'normal@machine.local', 'permanent' => true]);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertTrue($log->hasWarningThatContains('webhook-test'));
    }

    #[Test]
    public function the_providers_reason_for_refusing_a_registration_reaches_the_admin(): void
    {
        $this->setting('webhook_test_refuse', 'The API key is not valid.');

        $response = $this->send(
            $this->request('POST', '/api/mail/webhook/provision', [
                'authenticatedAs' => 1,
                'json' => [],
            ])
        );

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('The API key is not valid.', json_decode((string) $response->getBody(), true)['errors'][0]['detail']);
    }
}

class WebhookTestDriver implements DriverInterface, HandlesWebhooks, ProvisionsWebhooks
{
    /** @var list<string> */
    public static array $registered = [];

    public function availableSettings(): array
    {
        return [];
    }

    public function validate(SettingsRepositoryInterface $settings, Factory $validator): MessageBag
    {
        return new MessageBag();
    }

    public function canSend(): bool
    {
        return true;
    }

    public function buildTransport(SettingsRepositoryInterface $settings): TransportInterface
    {
        return new NullTransport();
    }

    public function getWebhookRequestParser(SettingsRepositoryInterface $settings): RequestParserInterface
    {
        return new WebhookTestParser();
    }

    public function getWebhookSigningSecret(SettingsRepositoryInterface $settings): ?string
    {
        return null;
    }

    public function webhookConfigured(SettingsRepositoryInterface $settings): bool
    {
        return $settings->get('webhook_test_configured') !== '0';
    }

    public function webhooksAvailable(SettingsRepositoryInterface $settings): bool
    {
        return $settings->get('webhook_test_available') !== '0';
    }

    public function isPermanentFailure(MailerDeliveryEvent $event): bool
    {
        return ($event->getPayload()['permanent'] ?? false) === true;
    }

    public function registerWebhook(SettingsRepositoryInterface $settings, string $url): void
    {
        if ($settings->get('webhook_test_refuse')) {
            throw new \RuntimeException($settings->get('webhook_test_refuse'));
        }

        static::$registered[] = $url;
        $settings->set('webhook_test_configured', '1');
    }

    public function unregisterWebhook(SettingsRepositoryInterface $settings, string $url): void
    {
        static::$registered = array_values(array_diff(static::$registered, [$url]));
    }
}

class WebhookTestParser implements RequestParserInterface
{
    public function parse(Request $request, #[\SensitiveParameter] string $secret): RemoteEvent|array|null
    {
        $payload = $request->toArray();

        if (isset($payload['expected_uri']) && $payload['expected_uri'] !== $request->getUri()) {
            throw new RejectWebhookException(406, 'Webhook URL does not match.');
        }

        if (isset($payload['reject'])) {
            throw new RejectWebhookException(406, $payload['reject']);
        }

        $event = new MailerDeliveryEvent($payload['name'], 'event-1', $payload);
        $event->setRecipientEmail($payload['email']);
        $event->setReason($payload['reason'] ?? '');

        return $event;
    }

    public function createSuccessfulResponse(): Response
    {
        return new Response();
    }

    public function createRejectedResponse(string $reason): Response
    {
        return new Response($reason, 406);
    }
}
