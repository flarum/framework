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
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\MessageBag;
use Symfony\Component\Mailer\Bridge\Mailgun\RemoteEvent\MailgunPayloadConverter;
use Symfony\Component\Mailer\Bridge\Mailgun\Transport\MailgunTransportFactory;
use Symfony\Component\Mailer\Bridge\Mailgun\Webhook\MailgunRequestParser;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;
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

    public function registerWebhook(SettingsRepositoryInterface $settings, string $url): void
    {
        $client = $this->apiClient($settings);

        // Mailgun keys webhooks by event type, so we PUT each type to make the
        // call idempotent: updating the URL if the webhook exists, creating it
        // if it doesn't. Only a 404 is expected/handled — any other non-2xx
        // (401 bad key, 400 bad payload, …) throws so the caller can surface a
        // real error instead of reporting a false success.
        foreach (self::WEBHOOK_EVENTS as $event) {
            $response = $client->request('PUT', "webhooks/$event", [
                'form_params' => ['url' => $url],
                'http_errors' => false,
            ]);

            $status = $response->getStatusCode();

            if ($status === 404) {
                // Webhook doesn't exist yet — create it (throws on failure).
                $client->request('POST', 'webhooks', [
                    'form_params' => ['id' => $event, 'url' => $url],
                ]);
            } elseif ($status < 200 || $status >= 300) {
                throw new \RuntimeException(sprintf(
                    'Mailgun rejected the webhook registration for "%s" (HTTP %d): %s',
                    $event,
                    $status,
                    (string) $response->getBody()
                ));
            }
        }
    }

    public function unregisterWebhook(SettingsRepositoryInterface $settings, string $url): void
    {
        $client = $this->apiClient($settings);

        foreach (self::WEBHOOK_EVENTS as $event) {
            $client->request('DELETE', "webhooks/$event", [
                'http_errors' => false,
            ]);
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
