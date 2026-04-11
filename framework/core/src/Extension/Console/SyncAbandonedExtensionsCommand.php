<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extension\Console;

use Flarum\Extension\AbandonedExtensionsFetcher;
use Illuminate\Console\Command;

class SyncAbandonedExtensionsCommand extends Command
{
    protected $signature = 'extensions:sync-abandoned {--notify : Email admins if newly abandoned extensions are found}';
    protected $description = 'Sync the list of abandoned extensions from flarum/abandoned-extensions.';

    public function handle(AbandonedExtensionsFetcher $fetcher): int
    {
        $this->info('Fetching abandoned extensions list...');

        try {
            $result = $fetcher->sync($this->option('notify'));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Stored {$result['count']} abandoned extension(s) matching installed packages.");

        if ($result['new']) {
            $this->info('Newly flagged: '.implode(', ', $result['new']));
        }

        return self::SUCCESS;
    }
}
