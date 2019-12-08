<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Flarum\Settings\SettingsRepositoryInterface;
use Swift_SendmailTransport;
use Swift_Transport;

class SendmailDriver implements DriverInterface
{
    public function availableSettings(): array
    {
        return [];
    }

    public function buildTransport(SettingsRepositoryInterface $settings): Swift_Transport
    {
        return new Swift_SendmailTransport;
    }
}
