<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Console;

use Carbon\Carbon;
use Flarum\Gdpr\Jobs\ErasureJob;
use Flarum\Gdpr\Jobs\GdprJob;
use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\Queue;

class ProcessEraseRequests extends Command
{
    const days = 30;
    protected $signature = 'gdpr:process-erase-requests';
    protected $description = 'Process open erase requests with community default erase mode.';

    public function handle(Queue $queue, SettingsRepositoryInterface $settings): void
    {
        ErasureRequest::query()
            ->whereNotNull('user_confirmed_at')
            ->whereNull('processed_at')
            ->where('user_confirmed_at', '<=', Carbon::now()->subDays(static::days))
            ->each(function (ErasureRequest $request) use ($queue, $settings) {
                $request->status = ErasureRequest::STATUS_PROCESSED;
                $request->processed_at = Carbon::now();
                $request->processed_mode = $request->processed_mode ?? $settings->get('flarum-gdpr.default-erasure');
                $request->processor_comment = 'Automatically processed after '.static::days.' through scheduled task.';
                $request->save();

                $queue->push(
                    job: new ErasureJob($request),
                    queue: GdprJob::$onQueue
                );
            });
    }
}
