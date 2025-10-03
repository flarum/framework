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
use Swift_SmtpTransport;
use Swift_Transport;

class SmtpDriver implements DriverInterface
{
    use ValidatesMailSettings;

    public function availableSettings(): array
    {
        return [
            'mail_host' => '', // a hostname, IPv4 address or IPv6 wrapped in []
            'mail_port' => '', // a number, defaults to 25
            'mail_encryption' => '', // "tls" or "ssl"
            'mail_username' => '',
            'mail_password' => '',
        ];
    }

    public function validate(SettingsRepositoryInterface $settings, Factory $validator): MessageBag
    {
        return $validator->make($settings->all(), [
            'mail_host' => ['required', $this->noWhitespace()],
            'mail_port' => ['nullable', 'integer', $this->noWhitespace()],
            'mail_encryption' => 'nullable|in:tls,ssl,TLS,SSL',
            'mail_username' => ['nullable', 'string', $this->noWhitespace()],
            'mail_password' => ['nullable', 'string', $this->noWhitespace()],
        ])->errors();
    }

    public function canSend(): bool
    {
        return true;
    }

    public function buildTransport(SettingsRepositoryInterface $settings): Swift_Transport
    {
        $transport = new Swift_SmtpTransport(
            $settings->get('mail_host'),
            $settings->get('mail_port'),
            $settings->get('mail_encryption')
        );

        $transport->setUsername($settings->get('mail_username'));
        $transport->setPassword($settings->get('mail_password'));

        return $transport;
    }
}
