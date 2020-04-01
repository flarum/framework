<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\User\NotificationPreference;
use Illuminate\Contracts\Container\Container;

class Notification implements ExtenderInterface
{
    private $channels = [];

    public function extend(Container $container, Extension $extension = null)
    {
        foreach ($this->channels as $channel => $enabled) {
            NotificationPreference::addChannel($channel, $enabled ?? []);
        }
    }

    public function addChannel(string $channel, array $enabledTypes = null)
    {
        $this->channels[$channel] = $enabledTypes;

        return $this;
    }
}
