<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Run;

/**
 * The work a phase actually consists of.
 *
 * Kept behind an interface so the driver has no idea what Composer is, what a
 * package is, or what a vendor directory looks like. That is not architecture
 * for its own sake: it is what lets the resumability tests kill and restart a
 * run hundreds of times in milliseconds, against work that is deterministic and
 * has no network. The real implementation lands in phases 4 and 5 and inherits
 * those guarantees rather than having to re-prove them.
 */
interface Steps
{
    /**
     * Everything this phase has to get through, as stable identifiers.
     *
     * 🚨 Must be deterministic and repeatable. A resumed run recomputes this and
     * carries on at the saved index, so if the list came back in a different
     * order the run would redo one item and skip another.
     *
     * @return list<string>
     */
    public function itemsFor(string $phase, Run $run): array;

    /**
     * Do exactly one item, and return a line for the log if it is worth one.
     *
     * 🚨 Must be idempotent. The driver saves progress after the item, so a
     * process killed between doing the work and recording it will do that item
     * again on resume. Everything in phase 2 was built for this: a stash whose
     * source is already gone is a no-op, not an error.
     */
    public function doItem(string $phase, string $item, Run $run): ?string;
}
