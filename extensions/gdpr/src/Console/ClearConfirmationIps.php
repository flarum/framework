<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Console;

use Carbon\Carbon;
use Flarum\Gdpr\Models\ErasureRequest;
use Illuminate\Console\Command;

class ClearConfirmationIps extends Command
{
    const RETENTION_DAYS = 90;

    protected $signature = 'gdpr:clear-confirmation-ips';
    protected $description = 'Clears stored confirmation IP addresses from erasure requests older than '.self::RETENTION_DAYS.' days.';

    public function handle(): void
    {
        ErasureRequest::query()
            ->whereNotNull('confirmation_ip')
            ->whereNotNull('user_confirmed_at')
            ->where('user_confirmed_at', '<=', Carbon::now()->subDays(static::RETENTION_DAYS))
            ->update(['confirmation_ip' => null]);
    }
}
