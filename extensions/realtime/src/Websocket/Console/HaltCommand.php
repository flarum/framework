<?php

namespace Flarum\Realtime\Websocket\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository;

class HaltCommand extends Command
{
    protected $signature = 'realtime:halt';
    protected $description = 'Forces the running daemon to stop.';

    public const KEY = 'blomstra-realtime-require-halt';

    public function handle(Repository $cache)
    {
        $cache->put(static::KEY, Carbon::now());

        $this->info('Signal to halt daemon fired.');
    }
}
