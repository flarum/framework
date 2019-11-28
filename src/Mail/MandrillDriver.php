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
use Illuminate\Mail\Transport\MandrillTransport;
use Swift_Transport;

class MandrillDriver implements DriverInterface
{
    public function availableSettings(): array
    {
        return [
            'mail_mandrill_secret',
        ];
    }

    public function buildTransport(SettingsRepositoryInterface $settings): Swift_Transport
    {
        return new MandrillTransport(
            new Client(['connect_timeout' => 60]),
            $settings->get('mail_mandrill_secret')
        );
    }
}
