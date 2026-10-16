<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Run;

/**
 * Builds the work for one named run.
 *
 * 🚨 A factory rather than a bound instance, because the run id must be an
 * ARGUMENT and never an ambient fact.
 *
 * The binding this replaced asked the store for "the latest run" once, when the
 * container first built it. Under PHP-FPM that is invisible — nothing survives
 * a request, so the latest run is always the one being stepped. Inside a queue
 * worker it is a real bug: the process is long-lived, the singleton outlives the
 * job that created it, and a worker that handled run A carries A's staging
 * directory and A's JOURNAL into run B. Applying B's packages while writing A's
 * journal makes the record of what happened disagree with what happened, so a
 * rollback afterwards restores the wrong files — silently, and only on forums
 * that have a queue, which are the ones least likely to be watching.
 *
 * Passing the id in makes that mistake unspellable.
 */
interface StepsFactory
{
    public function for(string $runId): Steps;
}
