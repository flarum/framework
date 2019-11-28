<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Aws\Ses\SesClient;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Mail\Transport\SesTransport;
use Swift_Transport;

class SesDriver implements DriverInterface
{
    public function availableSettings(): array
    {
        return [
            'mail_ses_key',
            'mail_ses_secret',
            'mail_ses_region',
        ];
    }

    public function buildTransport(SettingsRepositoryInterface $settings): Swift_Transport
    {
        $config = [
            'version' => 'latest',
            'service' => 'email',
            'region' => $settings->get('mail_ses_region'),
            'credentials' => [
                'key' => $settings->get('mail_ses_key'),
                'secret' => $settings->get('mail_ses_secret'),
            ],
        ];

        return new SesTransport(new SesClient($config));
    }
}
