<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Testing\integration\Setup;

use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Support\Collection;

/**
 * A transactions manager for the integration test harness.
 *
 * The harness wraps every test in a transaction that is rolled back (never
 * committed) on tear-down. With the stock manager, callbacks registered via
 * `Connection::afterCommit()` — including listeners implementing
 * `ShouldHandleEventsAfterCommit` — are attached to that open transaction and
 * discarded when it rolls back, so after-commit behaviour can never be
 * observed in tests (flarum/framework#4814).
 *
 * By reporting no applicable pending transactions, `addCallback()` runs each
 * callback immediately instead of deferring it. All other transaction
 * bookkeeping is left intact, so `db.transactions` stays bound and
 * `Connection::afterCommit()` still works (flarum/framework#4787).
 */
class InlineTransactionsManager extends DatabaseTransactionsManager
{
    public function callbackApplicableTransactions()
    {
        return new Collection();
    }
}
