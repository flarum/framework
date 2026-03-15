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
use Symfony\Component\Mailer\Bridge\Postmark\Transport\PostmarkTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

class PostmarkDriver implements DriverInterface
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
}
