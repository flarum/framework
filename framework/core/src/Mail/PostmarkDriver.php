<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\MessageBag;
use Symfony\Component\Mailer\Bridge\Postmark\RemoteEvent\PostmarkPayloadConverter;
use Symfony\Component\Mailer\Bridge\Postmark\Transport\PostmarkTransportFactory;
use Symfony\Component\Mailer\Bridge\Postmark\Webhook\PostmarkRequestParser;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\RemoteEvent\Event\Mailer\MailerDeliveryEvent;
use Symfony\Component\Webhook\Client\RequestParserInterface;

class PostmarkDriver implements DriverInterface, HandlesWebhooks
{
    use ValidatesMailSettings;

    public function availableSettings(): array
    {
        return [
            'mail_postmark_token' => '',
            'mail_postmark_message_stream' => '',
        ];
    }

    public function validate(SettingsRepositoryInterface $settings, Factory $validator): MessageBag
    {
        return $validator->make($settings->all(), [
            'mail_postmark_token' => ['required', $this->noWhitespace()],
            'mail_postmark_message_stream' => 'nullable|string',
        ])->errors();
    }

    public function canSend(): bool
    {
        return true;
    }

    public function buildTransport(SettingsRepositoryInterface $settings): TransportInterface
    {
        $factory = new PostmarkTransportFactory();

        $options = [];

        if ($stream = $settings->get('mail_postmark_message_stream')) {
            $options['message_stream'] = $stream;
        }

        return $factory->create(new Dsn(
            'postmark+api',
            'default',
            $settings->get('mail_postmark_token'),
            null,
            null,
            $options
        ));
    }

    public function getWebhookRequestParser(SettingsRepositoryInterface $settings): RequestParserInterface
    {
        // Credentials identify this forum's account and also work behind a
        // reverse proxy, whose address is not in Postmark's provider IP list.
        return new PostmarkRequestParser(new PostmarkPayloadConverter(), allowedIPs: []);
    }

    public function getWebhookSigningSecret(SettingsRepositoryInterface $settings): ?string
    {
        if (! $this->webhookConfigured($settings)) {
            return null;
        }

        return $settings->get('mail_webhook_username').':'.$settings->get('mail_webhook_password');
    }

    public function webhookConfigured(SettingsRepositoryInterface $settings): bool
    {
        $username = (string) $settings->get('mail_webhook_username');
        $password = (string) $settings->get('mail_webhook_password');

        return $username !== '' && $password !== '' && ! str_contains($username, ':');
    }

    public function webhooksAvailable(SettingsRepositoryInterface $settings): bool
    {
        return true;
    }

    public function isPermanentFailure(MailerDeliveryEvent $event): bool
    {
        return ($event->getPayload()['Inactive'] ?? false) === true;
    }
}
