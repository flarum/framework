<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Queue;

/**
 * Supplies the counts shown by the admin queue dashboard widget.
 *
 * Core binds a database-backed implementation. Extensions that provide a
 * different queue backend (fof/redis, fof/horizon) bind their own
 * implementation so the same core widget works for every driver, rather than
 * each shipping a parallel dashboard.
 */
interface QueueStatsProvider
{
    /**
     * Aggregate counts across all queues on the active connection.
     *
     * @return array{pending: int, reserved: int, failed: int}
     */
    public function totals(): array;

    /**
     * Per-queue pending/reserved counts, keyed by queue name.
     *
     * @return array<string, array{pending: int, reserved: int}>
     */
    public function queues(): array;
}
