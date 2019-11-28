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
use Illuminate\Mail\Transport\MailgunTransport;
use Swift_Transport;

class MailgunDriver implements DriverInterface
{
    public function availableSettings(): array
    {
        return [
            'mail_mailgun_secret', // the secret key
            'mail_mailgun_domain', // the API base URL
        ];
    }

    public function buildTransport(SettingsRepositoryInterface $settings): Swift_Transport
    {
        return new MailgunTransport(
            new Client(['connect_timeout' => 60]),
            $settings->get('mail_mailgun_secret'),
            $settings->get('mail_mailgun_domain')
        );
    }
}
