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
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mailer\Transport\TransportInterface;

class SmtpDriver implements DriverInterface
{
    public function __construct(
        protected EsmtpTransportFactory $factory
    ) {
    }

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
            'mail_host' => 'required',
            'mail_port' => 'nullable|integer',
            'mail_encryption' => 'nullable|in:tls,ssl,TLS,SSL',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
        ])->errors();
    }

    public function canSend(): bool
    {
        return true;
    }

    public function buildTransport(SettingsRepositoryInterface $settings): TransportInterface
    {
        return $this->factory->create(new Dsn(
            $settings->get('mail_encryption') === 'tls' ? 'smtps' : '',
            $settings->get('mail_host'),
            $settings->get('mail_username'),
            $settings->get('mail_password'),
            $settings->get('mail_port')
        ));
    }
}
