<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Queue;

use Illuminate\Database\ConnectionInterface;

/**
 * Queue stats for the database driver, read from the queue_jobs and
 * queue_failed_jobs tables. A job is "reserved" (in-flight) once a worker has
 * picked it up (reserved_at is set); otherwise it is pending.
 */
class DatabaseQueueStatsProvider implements QueueStatsProvider
{
    public function __construct(
        protected ConnectionInterface $db
    ) {
    }

    public function totals(): array
    {
        $pending = (int) $this->db->table('queue_jobs')->whereNull('reserved_at')->count();
        $reserved = (int) $this->db->table('queue_jobs')->whereNotNull('reserved_at')->count();
        $failed = (int) $this->db->table('queue_failed_jobs')->count();

        return compact('pending', 'reserved', 'failed');
    }

    public function queues(): array
    {
        $rows = $this->db->table('queue_jobs')
            ->selectRaw('queue, sum(case when reserved_at is null then 1 else 0 end) as pending, sum(case when reserved_at is not null then 1 else 0 end) as reserved')
            ->groupBy('queue')
            ->get();

        $queues = [];

        foreach ($rows as $row) {
            $queues[$row->queue] = [
                'pending' => (int) $row->pending,
                'reserved' => (int) $row->reserved,
            ];
        }

        return $queues;
    }
}
