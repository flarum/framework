<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Flarum\Settings\SettingsRepositoryInterface;
use Swift_SmtpTransport;
use Swift_Transport;

class SmtpDriver implements DriverInterface
{
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
