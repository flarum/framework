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
        // Postmark authenticates inbound webhooks by verifying the request
        // originates from its published IP range (handled inside the parser's
        // request matcher), not by a shared secret.
        return new PostmarkRequestParser(new PostmarkPayloadConverter());
    }

    public function getWebhookSigningSecret(SettingsRepositoryInterface $settings): ?string
    {
        // Postmark does not use a signing secret; verification is IP-based.
        return null;
    }

    public function webhookConfigured(SettingsRepositoryInterface $settings): bool
    {
        // Postmark needs no extra webhook config — verification is by IP, and
        // the admin registers the URL in Postmark's dashboard.
        return true;
    }
}
