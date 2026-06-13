<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository;

class HaltCommand extends Command
{
    protected $signature = 'realtime:halt';
    protected $description = 'Forces the running daemon to stop.';

    public const KEY = 'flarum.realtime.require-halt';

    public function handle(Repository $cache): void
    {
        $cache->put(static::KEY, Carbon::now());

        $this->info('Signal to halt daemon fired.');
    }
}
