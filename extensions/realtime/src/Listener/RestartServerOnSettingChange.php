<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Listener;

use Carbon\Carbon;
use Flarum\Realtime\Websocket\Console\HaltCommand;
use Flarum\Settings\Event\Saved;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Str;

/**
 * The websocket server is a long-running process that reads its settings once,
 * so admin changes to realtime settings (e.g. enabling restricted-tag index
 * typing) are not picked up until it restarts. When any `flarum-realtime.*`
 * setting changes, signal the running daemon to halt — the same mechanism the
 * `realtime:halt` command and extension-toggle watcher use — and the process
 * supervisor brings it back up with the new settings.
 */
class RestartServerOnSettingChange
{
    public function __construct(
        protected Repository $cache
    ) {
    }

    public function handle(Saved $event): void
    {
        foreach (array_keys($event->settings) as $key) {
            // Scope strictly to this extension's own settings so saves for
            // other extensions / core never restart the websocket server.
            if (Str::startsWith($key, 'flarum-realtime.')) {
                $this->cache->put(HaltCommand::KEY, Carbon::now());

                return;
            }
        }
    }
}
