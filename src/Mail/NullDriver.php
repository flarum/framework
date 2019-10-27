<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Flarum\Settings\SettingsRepositoryInterface;
use Swift_NullTransport;
use Swift_Transport;

class NullDriver implements DriverInterface
{
    public function availableSettings(): array
    {
        return [];
    }

    public function requiredFields(): array
    {
        return [];
    }

    public function buildTransport(SettingsRepositoryInterface $settings): Swift_Transport
    {
        return new Swift_NullTransport();
    }
}
