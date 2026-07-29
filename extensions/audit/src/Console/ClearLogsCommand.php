<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Console;

use Carbon\Carbon;
use Flarum\Audit\AuditLog;
use Flarum\Audit\AuditLogger;
use Flarum\Extension\ExtensionManager;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;

class ClearLogsCommand extends Command
{
    protected $signature = 'audit:clear {--before= : Delete entries older than the specified date. Any Carbon syntax is supported} {--reset : Delete all data, database tables and disable extension} {--force : Don\'t ask for confirmation}';
    protected $description = 'Permanently destroy audit log entries';

    public function handle(Connection $db, ExtensionManager $manager): void
    {
        if ($before = $this->option('before')) {
            $beforeDate = Carbon::parse($before);

            $query = AuditLog::query()->where('created_at', '<', $beforeDate);
            $count = $query->count();

            if ($count === 0) {
                $this->warn('There are no entries matching this query. Aborting.');

                return;
            }

            if (! $this->option('force') && ! $this->confirm($count.' records will be deleted from the log table. Continue?')) {
                $this->warn('Aborting.');

                return;
            }

            $query->delete();
            $this->info($count.' records deleted.');
            $this->info('All done!');

            AuditLogger::log('audit_log_cleared', [
                'before' => $beforeDate->toIso8601String(),
                'deleted_count' => $count,
            ]);

            return;
        }

        if ($this->option('reset')) {
            if (! $this->option('force') && ! $this->confirm('This will delete the audit log database table and disable the extension. Continue?')) {
                $this->warn('Aborting.');

                return;
            }

            AuditLogger::$disabled = true;

            $db->getSchemaBuilder()->dropIfExists('audit_log');
            $this->info('Table deleted.');

            // Delete the migration entries to ensure they will run again next time the extension is re-enabled
            $db->table('migrations')->where('extension', 'flarum-audit')->delete();
            $this->info('Migration entries deleted.');

            $manager->disable('flarum-audit');
            $this->info('Extension disabled.');

            $this->info('All done!');

            return;
        }

        $this->warn('No option chosen. Run with --help for the list of options.');
    }
}
