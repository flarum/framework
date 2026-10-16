<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Run;

use RuntimeException;

/**
 * This item is not finished, and it is not failing either. Come back.
 *
 * 🚨 The third answer a step needs, and its absence is what let a completed run
 * lie.
 *
 * Every step could previously say "done" or "failed", so a step that had
 * done everything it could but whose EFFECT was not live yet had to say "done"
 * and put the caveat in the log. A caveat in a log is not a state: the run went
 * green, the admin screen said Finished, and the next thing anybody did assumed
 * the update was in effect. On the update that clears PHP's compiled-code cache
 * from a queue worker — which cannot reach the web server's cache — that gap is
 * up to a minute of the site serving code that does not match its own database.
 *
 * A step throwing this keeps the run in progress at the same item. Every driver
 * already calls step() again — the admin page polls, the job re-dispatches, the
 * cron ticks — so waiting costs nothing and needs no new machinery.
 */
class NotYet extends RuntimeException
{
    public function __construct(string $why)
    {
        parent::__construct($why);
    }
}
