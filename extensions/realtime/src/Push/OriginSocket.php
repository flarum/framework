<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Push;

use Illuminate\Container\Container;

/**
 * The websocket connection of the browser tab that sent the current API
 * request, if it said.
 */
class OriginSocket
{
    private ?string $id = null;

    public function set(?string $id): void
    {
        $this->id = $id !== null && preg_match('/\A\d{1,10}\.\d{1,10}\z/', $id) ? $id : null;
    }

    public function get(): ?string
    {
        return $this->id;
    }

    /**
     * Null where nothing registered it (a bare container in unit tests).
     */
    public static function current(): ?string
    {
        $container = Container::getInstance();

        return $container->bound(self::class) ? $container->make(self::class)->get() : null;
    }
}
