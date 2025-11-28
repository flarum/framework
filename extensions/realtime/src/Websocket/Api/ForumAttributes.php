<?php


namespace Flarum\Realtime\Websocket\Api;

use Flarum\Realtime\Websocket\Settings;
use Flarum\Api\Schema\Str;

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
