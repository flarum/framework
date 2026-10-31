<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Flarum\Settings\SettingsRepositoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Query;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\MessageBag;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Symfony\Component\Mailer\Bridge\Mailgun\RemoteEvent\MailgunPayloadConverter;
use Symfony\Component\Mailer\Bridge\Mailgun\Transport\MailgunTransportFactory;
use Symfony\Component\Mailer\Bridge\Mailgun\Webhook\MailgunRequestParser;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\RemoteEvent\Event\Mailer\MailerDeliveryEvent;
use Symfony\Component\Webhook\Client\RequestParserInterface;

class MailgunDriver implements DriverInterface, HandlesWebhooks, ProvisionsWebhooks
{
    use ValidatesMailSettings;

    /**
     * Delivery-event webhook types Flarum registers with Mailgun. We only care
     * about permanent (hard) failures and complaints; temporary failures are
     * deliberately excluded so we never suppress over a transient problem.
     */
    protected const WEBHOOK_EVENTS = ['permanent_fail', 'complained'];

    protected const MAX_WEBHOOK_URLS = 3;

    public function availableSettings(): array
    {
        return [
            'mail_mailgun_secret' => '', // the secret key
            'mail_mailgun_domain' => '', // the API base URL
            'mail_mailgun_region' => [ // region's endpoint
                'api.mailgun.net' => 'US',
                'api.eu.mailgun.net' => 'EU',
            ],
            'mail_mailgun_webhook_signing_key' => '', // HTTP webhook signing key (verifies inbound webhooks)
        ];
    }

    public function validate(SettingsRepositoryInterface $settings, Factory $validator): MessageBag
    {
        return $validator->make($settings->all(), [
            'mail_mailgun_secret' => ['required', $this->noWhiteSpace()],
            'mail_mailgun_domain' => 'required|regex:/^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/',
            'mail_mailgun_region' => 'required|in:api.mailgun.net,api.eu.mailgun.net',
        ])->errors();
    }

    public function canSend(): bool
    {
        return true;
    }

    public function buildTransport(SettingsRepositoryInterface $settings): TransportInterface
    {
        $factory = new MailgunTransportFactory();

        return $factory->create(new Dsn(
            'mailgun+api',
            $settings->get('mail_mailgun_region'),
            $settings->get('mail_mailgun_secret'),
            $settings->get('mail_mailgun_domain')
        ));
    }

    public function getWebhookRequestParser(SettingsRepositoryInterface $settings): RequestParserInterface
    {
        return new MailgunRequestParser(new MailgunPayloadConverter());
    }

    public function getWebhookSigningSecret(SettingsRepositoryInterface $settings): ?string
    {
        return $settings->get('mail_mailgun_webhook_signing_key') ?: null;
    }

    public function webhookConfigured(SettingsRepositoryInterface $settings): bool
    {
        // Mailgun verifies inbound webhooks with the signing key, so without it
        // every webhook is rejected — treat as not configured.
        return $this->getWebhookSigningSecret($settings) !== null;
    }

    public function webhooksAvailable(SettingsRepositoryInterface $settings): bool
    {
        return true;
    }

    public function isPermanentFailure(MailerDeliveryEvent $event): bool
    {
        return true;
    }

    public function registerWebhook(SettingsRepositoryInterface $settings, string $url): void
    {
        $client = $this->apiClient($settings);

        foreach (self::WEBHOOK_EVENTS as $event) {
            $urls = $this->webhookUrls($client, $event);

            if (in_array($url, $urls, true)) {
                continue;
            }

            $others = $this->withoutEndpoint($urls, $url);

            if (count($others) >= self::MAX_WEBHOOK_URLS) {
                throw new RuntimeException(sprintf(
                    'Mailgun already sends the "%s" webhook to %d other URLs, the most it allows. Remove one in the Mailgun dashboard and try again.',
                    $event,
                    count($others)
                ));
            }

            if ($urls === []) {
                $this->assertSuccessful($client->request('POST', 'webhooks', [
                    'form_params' => ['id' => $event, 'url' => $url],
                    'http_errors' => false,
                ]), $event);

                continue;
            }

            $this->putWebhookUrls($client, $event, [...$others, $url]);
        }
    }

    public function unregisterWebhook(SettingsRepositoryInterface $settings, string $url): void
    {
        $client = $this->apiClient($settings);

        foreach (self::WEBHOOK_EVENTS as $event) {
            $urls = $this->webhookUrls($client, $event);
            $remaining = $this->withoutEndpoint($urls, $url);

            if ($remaining === $urls) {
                continue;
            }

            if ($remaining === []) {
                $this->assertSuccessful($client->request('DELETE', "webhooks/$event", ['http_errors' => false]), $event);

                continue;
            }

            $this->putWebhookUrls($client, $event, $remaining);
        }
    }

    /**
     * The URLs that are not the given endpoint, whatever credentials either carries.
     *
     * @param list<string> $urls
     * @return list<string>
     */
    protected function withoutEndpoint(array $urls, string $url): array
    {
        $endpoint = fn (string $url) => preg_replace('#^([a-z][a-z0-9+.-]*://)[^/@]*@#i', '$1', $url);

        return array_values(array_filter($urls, fn (string $existing) => $endpoint($existing) !== $endpoint($url)));
    }

    /**
     * @return list<string>
     */
    protected function webhookUrls(Client $client, string $event): array
    {
        $response = $client->request('GET', "webhooks/$event", ['http_errors' => false]);

        if ($response->getStatusCode() === 404) {
            return [];
        }

        $this->assertSuccessful($response, $event);

        return array_values((array) (json_decode((string) $response->getBody(), true)['webhook']['urls'] ?? []));
    }

    /**
     * @param list<string> $urls
     */
    protected function putWebhookUrls(Client $client, string $event, array $urls): void
    {
        $this->assertSuccessful($client->request('PUT', "webhooks/$event", [
            'body' => Query::build(['url' => $urls]),
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'http_errors' => false,
        ]), $event);
    }

    protected function assertSuccessful(ResponseInterface $response, string $event): void
    {
        $status = $response->getStatusCode();

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException(sprintf(
                'Mailgun rejected the request for the "%s" webhook (HTTP %d): %s',
                $event,
                $status,
                (string) $response->getBody()
            ));
        }
    }

    protected function apiClient(SettingsRepositoryInterface $settings): Client
    {
        $region = $settings->get('mail_mailgun_region') ?: 'api.mailgun.net';
        $domain = $settings->get('mail_mailgun_domain');

        return new Client([
            'base_uri' => "https://$region/v3/domains/$domain/",
            'auth' => ['api', $settings->get('mail_mailgun_secret')],
        ]);
    }
}
