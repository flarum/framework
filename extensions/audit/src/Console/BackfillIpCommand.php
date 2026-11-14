<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Console;

use Flarum\Audit\IpRange;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;

/**
 * Fills in `ip_hex` for entries logged before it existed, so partial-address
 * and subnet searches find them too.
 *
 * Kept out of the migration because an audit log can hold millions of rows.
 * It walks the table by id in chunks, one transaction and one UPDATE per
 * distinct address in each chunk, and only touches rows still missing
 * `ip_hex`, so it can be stopped and run again. Values that aren't IP
 * addresses are left as they are.
 */
class BackfillIpCommand extends Command
{
    protected $signature = 'audit:backfill-ip {--chunk=1000 : Number of log entries to process per batch}';
    protected $description = 'Index the IP addresses of audit log entries recorded before partial and subnet IP search existed';

    public function handle(ConnectionInterface $db): int
    {
        $chunk = (int) $this->option('chunk');

        if ($chunk < 1) {
            $this->error('--chunk must be a positive number.');

            return self::FAILURE;
        }

        $pending = $db->table('audit_log')->whereNull('ip_hex')->whereNotNull('ip_address');

        $total = (clone $pending)->count();

        if ($total === 0) {
            $this->info('Every audit log entry is already indexed.');

            return self::SUCCESS;
        }

        $updated = 0;
        $skipped = 0;

        $this->output->progressStart($total);

        (clone $pending)->select(['id', 'ip_address'])->chunkById($chunk, function ($rows) use ($db, &$updated, &$skipped) {
            $db->transaction(function () use ($db, $rows, &$updated, &$skipped) {
                foreach ($rows->groupBy('ip_address') as $ip => $group) {
                    $hex = IpRange::normalise((string) $ip);

                    if ($hex === null) {
                        $skipped += $group->count();

                        continue;
                    }

                    $updated += $db->table('audit_log')
                        ->whereIn('id', $group->pluck('id'))
                        ->whereNull('ip_hex')
                        ->update(['ip_hex' => $hex]);
                }
            });

            $this->output->progressAdvance($rows->count());
        });

        $this->output->progressFinish();

        $this->info($updated === 0 ? 'Nothing left to index.' : "$updated entries indexed.");

        if ($skipped > 0) {
            $this->warn("$skipped entries have an IP address value that isn't a valid IP address and were left as they are. They can still be found by an exact ip: search.");
        }

        return self::SUCCESS;
    }
}
