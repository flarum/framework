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
    use ValidatesMailSettings;

    public function __construct(
        protected EsmtpTransportFactory $factory
    ) {
    }

    public function availableSettings(): array
    {
        return [
            'mail_host' => '', // a hostname, IPv4 address or IPv6 wrapped in []
            'mail_port' => '', // a number, defaults to 25
            'mail_encryption' => [ // Dropdown options for encryption
                '' => 'None',
                'tls' => 'TLS',
                'ssl' => 'SSL',
            ],
            'mail_username' => '',
            'mail_password' => '',
        ];
    }

    public function validate(SettingsRepositoryInterface $settings, Factory $validator): MessageBag
    {
        return $validator->make($settings->all(), [
            'mail_host' => ['required', $this->noWhiteSpace()],
            'mail_port' => ['nullable', 'integer', $this->noWhiteSpace()],
            'mail_encryption' => 'nullable|in:tls,ssl,TLS,SSL',
            'mail_username' => ['nullable', 'string', $this->noWhiteSpace()],
            'mail_password' => ['nullable', 'string', $this->noWhiteSpace()],
        ])->errors();
    }

    public function canSend(): bool
    {
        return true;
    }

    public function buildTransport(SettingsRepositoryInterface $settings): TransportInterface
    {
        $encryption = strtolower((string) $settings->get('mail_encryption'));

        // 'ssl' means implicit TLS/SSL (smtps://), typically used with port 465
        // 'tls' or empty means STARTTLS (smtp://), typically used with port 587 or 25
        $scheme = ($encryption === 'ssl') ? 'smtps' : 'smtp';

        return $this->factory->create(new Dsn(
            $scheme,
            $settings->get('mail_host'),
            $settings->get('mail_username'),
            $settings->get('mail_password'),
            $settings->get('mail_port')
        ));
    }
}
