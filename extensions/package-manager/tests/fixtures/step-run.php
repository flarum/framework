<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

/*
 * Advances a run a few times, then dies abruptly — standing in for a request
 * that the host cut off, or a browser tab that was closed.
 *
 * Each completed item is appended to a log file so the test can see, across
 * many processes, that everything was done.
 *
 * argv: <dir> <runId> <itemsJson> <logPath> <stepsBeforeDying> [killInsideItem]
 *
 * killInsideItem dies BETWEEN doing an item and the driver recording it — the
 * one gap where work is repeated on resume, and therefore the only place
 * idempotency is actually load-bearing. Killing after a step returns proves
 * cross-process resumption but never touches it.
 */
require is_file(__DIR__.'/../../vendor/autoload.php') ? __DIR__.'/../../vendor/autoload.php' : __DIR__.'/../../../../vendor/autoload.php';

use Flarum\ExtensionManager\Run\Run;
use Flarum\ExtensionManager\Run\RunStore;
use Flarum\ExtensionManager\Run\StepRunner;
use Flarum\ExtensionManager\Run\Steps;

[$script, $dir, $runId, $itemsJson, $logPath, $budget] = $argv;
$killInside = $argv[6] ?? '';

$plan = json_decode($itemsJson, true, 512, JSON_THROW_ON_ERROR);

$steps = new class($plan, $logPath, $killInside) implements Steps {
    public function __construct(private array $plan, private string $log, private string $killInside)
    {
    }

    public function itemsFor(string $phase, Run $run): array
    {
        return $this->plan[$phase] ?? [];
    }

    public function doItem(string $phase, string $item, Run $run): ?string
    {
        // A moment of work, so concurrent drivers genuinely overlap rather than
        // finishing too fast to collide.
        usleep(40000);

        // The record of the work, written before the driver saves progress —
        // so a kill in between shows up as a repeat, which is exactly the
        // behaviour the test is there to characterise.
        file_put_contents($this->log, "$phase:$item\n", FILE_APPEND | LOCK_EX);

        if ($this->killInside !== '' && $item === $this->killInside) {
            // The work is done and on disk; the driver has NOT yet recorded it.
            posix_kill(posix_getpid(), SIGKILL);
        }

        return null;
    }
};

$store = new RunStore($dir);
$runner = new StepRunner($store, $steps, fn () => time(), $dir.'/locks');

if ($store->load($runId) === null) {
    $runner->begin($runId);
}

for ($i = 0; $i < (int) $budget; $i++) {
    $run = $runner->step($runId);

    if ($run->isFinished()) {
        echo "finished\n";
        exit(0);
    }
}

// Out of budget: die the way a host does, with no unwinding.
posix_kill(posix_getpid(), SIGKILL);
