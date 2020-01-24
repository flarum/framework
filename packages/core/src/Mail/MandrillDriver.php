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
use Illuminate\Mail\Transport\MandrillTransport;
use Illuminate\Support\MessageBag;
use Swift_Transport;

class MandrillDriver implements DriverInterface
{
    public function availableSettings(): array
    {
        return [
            'mail_mandrill_secret' => '',
        ];
    }

    public function validate(SettingsRepositoryInterface $settings, Factory $validator): MessageBag
    {
        return $validator->make($settings->all(), [
            'mail_mandrill_secret' => 'required',
        ])->errors();
    }

    public function canSend(): bool
    {
        return true;
    }

    public function buildTransport(SettingsRepositoryInterface $settings): Swift_Transport
    {
        return new MandrillTransport(
            new Client(['connect_timeout' => 60]),
            $settings->get('mail_mandrill_secret')
        );
    }
}
