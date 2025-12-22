<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Api;

use Flarum\Api\Schema\Str;
use Flarum\Realtime\Websocket\Settings;

class ForumAttributes
{
    public function __construct(
        protected Settings $settings
    ) {
    }

    public function __invoke(): array
    {
        return [
            Str::make('websocket.key')
                ->get(fn () => $this->settings->appKey),
            Str::make('websocket.host')
                ->get(fn () => $this->settings->jsClientHost),
            Str::make('websocket.port')
                ->get(fn () => $this->settings->jsClientPort),
            Str::make('websocket.secure')
                ->get(fn () => $this->settings->jsClientSecure),
        ];
    }
}
