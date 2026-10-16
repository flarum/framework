<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Prune;

use Flarum\ExtensionManager\Lang;
use Flarum\ExtensionManager\Apply\Journal;
use Flarum\ExtensionManager\Plan\Change;
use Flarum\ExtensionManager\Run\Run;
use RuntimeException;
use Throwable;

/**
 * Tidies the rollback copies, without ever taking one a rollback could need.
 *
 * Every update moves the versions it replaces into storage/extension-manager/trash,
 * and until this existed nothing ever took them out again: fbsfb.com reached
 * 971 MB across 19 updates, half of it one extension's leftover node_modules.
 *
 * 🚨 THE RULE, and the reason it is shaped this way.
 *
 * Only the NEWEST run can be rolled back — RollbackController, the console and
 * the screen all act on `RunStore::latest()` and nothing else. So the copies
 * that rollback could actually reach are exactly the ones the newest run's
 * journal names. Those are never removed, whatever the settings say. Neither is
 * anything belonging to a run that has not finished (it is still writing to the
 * trash), nor what the most recent FINISHED run needs, nor anything that
 * changed in the last day.
 *
 * Older copies are a by-hand safety net — the wowcraft outage was recovered by
 * copying them back — so they are kept too, for whichever is MORE generous of
 * "the last N runs" and "the last N days". Only what falls outside all of that
 * is removed:
 *
 *   - **expired** — a copy only an older run's journal names, past the window
 *   - **leftover** — `.rolledback` / `.superseded`: the newer version a
 *     rollback moved aside. Nothing ever reads these back.
 *   - **orphan** — named by no journal at all
 *
 * Run directories (journal, saved manifests, staging) follow the same rule.
 * The run's own JSON summary is kept: it is a few hundred bytes, and it is the
 * record that an update happened.
 *
 * 🚨 Fails CLOSED. A protected run whose journal cannot be read stops the whole
 * prune; a run summary that cannot be parsed protects that run's directory. A
 * prune that does nothing is a nuisance. A prune that guessed is an outage.
 */
class Pruner
{
    /** Anything that changed more recently than this is left alone. */
    public const GRACE = 86400;

    /** @var callable():int */
    private $clock;

    public function __construct(
        private string $dataDir,
        private Retention $retention,
        ?callable $clock = null,
    ) {
        $this->clock = $clock ?? fn () => time();
    }

    public function trashDir(): string
    {
        return rtrim($this->dataDir, '/').'/trash';
    }

    public function runsDir(): string
    {
        return rtrim($this->dataDir, '/').'/runs';
    }

    /**
     * Decide, without touching anything.
     *
     * @param bool $measure also size what is KEPT, for the screen's total. The
     *        finished-run prune skips that: it walks every file in the trash.
     * @return array{
     *     remove: list<array<string,mixed>>,
     *     keep: list<array<string,mixed>>,
     *     protectedRuns: array<string,string>,
     *     settings: array{keepDays:int, keepRuns:int},
     *     trashBytes: ?int,
     *     removeBytes: int,
     * }
     */
    public function plan(bool $measure = true): array
    {
        $now = ($this->clock)();
        $settings = $this->retention->settings();

        [$runs, $unreadable] = $this->runs();
        $protected = $this->protectedRuns($runs, $unreadable, $settings, $now);

        $protectedRefs = [];
        $otherRefs = [];

        foreach ($runs as $run) {
            if (isset($protected[$run->id])) {
                // 🚨 Fails closed: an unreadable journal here throws, and nothing is removed.
                foreach ($this->references($run->id, true) as $name) {
                    $protectedRefs[$name] ??= $run->id;
                }
            } else {
                foreach ($this->references($run->id, false) as $name) {
                    $otherRefs[$name] ??= $run->id;
                }
            }
        }

        foreach (array_keys($unreadable) as $id) {
            foreach ($this->references($id, true) as $name) {
                $protectedRefs[$name] ??= $id;
            }
        }

        $remove = [];
        $keep = [];

        foreach ($this->trashEntries($now, $protectedRefs, $otherRefs, $protected) as $row) {
            $row['kind'] = 'trash';

            if ($row['remove']) {
                $remove[] = $row;
            } else {
                $keep[] = $row;
            }
        }

        foreach ($this->runDirs($now, $runs, $protected) as $row) {
            $row['kind'] = 'run';

            if ($row['remove']) {
                $remove[] = $row;
            } else {
                $keep[] = $row;
            }
        }

        $trash = is_dir($this->trashDir()) ? new Contained($this->trashDir()) : null;
        $runsDir = is_dir($this->runsDir()) ? new Contained($this->runsDir()) : null;

        $removeBytes = 0;

        foreach ($remove as $i => $row) {
            $where = $row['kind'] === 'trash' ? $trash : $runsDir;
            $remove[$i]['bytes'] = $where?->size($row['name']) ?? 0;
            $removeBytes += $remove[$i]['bytes'];
        }

        $trashBytes = null;

        if ($measure) {
            $trashBytes = 0;

            foreach ($remove as $row) {
                $trashBytes += $row['kind'] === 'trash' ? $row['bytes'] : 0;
            }

            foreach ($keep as $i => $row) {
                if ($row['kind'] === 'trash') {
                    $keep[$i]['bytes'] = $trash?->size($row['name']) ?? 0;
                    $trashBytes += $keep[$i]['bytes'];
                }
            }
        }

        $strip = fn (array $rows) => array_values(array_map(function (array $row) {
            unset($row['remove']);

            return $row;
        }, $rows));

        return [
            'remove' => $strip($remove),
            'keep' => $strip($keep),
            'protectedRuns' => $protected,
            'settings' => $settings,
            'trashBytes' => $trashBytes,
            'removeBytes' => $removeBytes,
        ];
    }

    /**
     * Decide, then remove.
     *
     * 🚨 The decision is made immediately before the removal, in the same call,
     * so there is no window in which a stale plan can be carried out against a
     * tree that has moved on — and anything that arrives in between is younger
     * than the grace period and kept anyway.
     *
     * @param ?float $budget seconds; stop starting new removals after this. The
     *        end-of-update prune passes one so a host that cuts requests at 30
     *        seconds never has its update marked failed by housekeeping. What is
     *        left is picked up by the next prune.
     * @return array<string,mixed> the summary, as recorded
     */
    public function prune(string $trigger, ?float $budget = null): array
    {
        $started = microtime(true);
        $plan = $this->plan(false);

        $trash = is_dir($this->trashDir()) ? new Contained($this->trashDir()) : null;
        $runs = is_dir($this->runsDir()) ? new Contained($this->runsDir()) : null;

        $removed = [];
        $failed = [];
        $freed = 0;
        $complete = true;

        foreach ($plan['remove'] as $row) {
            if ($budget !== null && (microtime(true) - $started) > $budget) {
                $complete = false;

                break;
            }

            $where = $row['kind'] === 'trash' ? $trash : $runs;

            if ($where === null) {
                continue;
            }

            try {
                $freed += $where->remove($row['name']);
                $removed[] = $row['kind'].':'.$row['name'];
            } catch (Throwable $e) {
                $failed[] = $row['kind'].':'.$row['name'].' — '.$e->getMessage();
            }
        }

        $summary = [
            'at' => ($this->clock)(),
            'trigger' => $trigger,
            'removed' => count($removed),
            'freed' => $freed,
            'complete' => $complete,
            'failed' => $failed,
            'names' => $removed,
        ];

        $this->retention->recordPrune($summary);

        return $summary;
    }

    /**
     * Which runs keep their copies, and why.
     *
     * @param list<Run> $runs newest first
     * @param array<string,true> $unreadable
     * @param array{keepDays:int, keepRuns:int} $settings
     * @return array<string,string> run id => reason
     */
    private function protectedRuns(array $runs, array $unreadable, array $settings, int $now): array
    {
        $out = [];

        foreach (array_keys($unreadable) as $id) {
            $out[$id] = 'its record could not be read, so it is kept to be safe';
        }

        $finishedSeen = false;

        foreach ($runs as $i => $run) {
            $reason = null;

            if ($i === 0) {
                $reason = 'the latest update — this is what Roll back undoes';
            } elseif (! $run->isFinished()) {
                $reason = 'still in progress';
            } elseif (! $finishedSeen && $run->state === Run::DONE) {
                $reason = 'the most recent finished update';
            } elseif ($i < $settings['keepRuns']) {
                $reason = "one of the last {$settings['keepRuns']} updates";
            } elseif ($now - max($run->startedAt, $run->movedAt) <= $settings['keepDays'] * 86400) {
                $reason = "from the last {$settings['keepDays']} days";
            }

            if ($run->state === Run::DONE) {
                $finishedSeen = true;
            }

            if ($reason !== null) {
                $out[$run->id] = $reason;
            }
        }

        return $out;
    }

    /**
     * @param array<string,string> $protectedRefs trash name => run id
     * @param array<string,string> $otherRefs trash name => run id
     * @param array<string,string> $protected run id => reason
     * @return list<array<string,mixed>>
     */
    private function trashEntries(int $now, array $protectedRefs, array $otherRefs, array $protected): array
    {
        if (! is_dir($this->trashDir())) {
            return [];
        }

        $trash = new Contained($this->trashDir());
        $out = [];

        foreach ($trash->names() as $name) {
            $row = ['name' => $name, 'run' => null];

            if (isset($protectedRefs[$name])) {
                $run = $protectedRefs[$name];
                $out[] = $row + ['remove' => false, 'run' => $run, 'reason' => 'rollback', 'why' => $protected[$run] ?? 'kept'];

                continue;
            }

            if ($now - $trash->changedAt($name) < self::GRACE) {
                $out[] = $row + ['remove' => false, 'reason' => 'grace', 'why' => 'changed in the last day'];

                continue;
            }

            if (str_ends_with($name, '.rolledback') || str_ends_with($name, '.superseded')) {
                $out[] = $row + ['remove' => true, 'reason' => 'leftover', 'why' => 'the newer version a rollback moved aside'];
            } elseif (isset($otherRefs[$name])) {
                $out[] = ['name' => $name, 'run' => $otherRefs[$name], 'remove' => true, 'reason' => 'expired',
                    'why' => 'from an update that can no longer be rolled back'];
            } else {
                $out[] = $row + ['remove' => true, 'reason' => 'orphan', 'why' => 'no update refers to it'];
            }
        }

        return $out;
    }

    /**
     * @param list<Run> $runs
     * @param array<string,string> $protected
     * @return list<array<string,mixed>>
     */
    private function runDirs(int $now, array $runs, array $protected): array
    {
        if (! is_dir($this->runsDir())) {
            return [];
        }

        $dir = new Contained($this->runsDir());
        $known = [];

        foreach ($runs as $run) {
            $known[$run->id] = true;
        }

        $out = [];

        foreach ($dir->names() as $name) {
            $path = $this->runsDir().'/'.$name;

            // Run directories only. The JSON summaries are kept, always.
            if (is_link($path) || ! is_dir($path) || ! preg_match('/^[A-Za-z0-9_-]{1,64}$/', $name)) {
                continue;
            }

            $row = ['name' => $name, 'run' => $name];

            if (isset($protected[$name])) {
                $out[] = $row + ['remove' => false, 'reason' => 'rollback', 'why' => $protected[$name]];
            } elseif ($now - $dir->changedAt($name) < self::GRACE) {
                $out[] = $row + ['remove' => false, 'reason' => 'grace', 'why' => 'changed in the last day'];
            } elseif (isset($known[$name])) {
                $out[] = $row + ['remove' => true, 'reason' => 'expired', 'why' => 'from an update that can no longer be rolled back'];
            } else {
                $out[] = $row + ['remove' => true, 'reason' => 'orphan', 'why' => 'no update record refers to it'];
            }
        }

        return $out;
    }

    /**
     * Every run summary, newest first, plus the ids whose summary is unreadable.
     *
     * 🚨 Read here rather than through RunStore::all(), which skips a file it
     * cannot parse. Skipping is right for a screen and wrong for a prune: an
     * unreadable summary might be the newest run, and its copies are then
     * exactly the ones that must not go.
     *
     * @return array{0: list<Run>, 1: array<string,true>}
     */
    private function runs(): array
    {
        $runs = [];
        $unreadable = [];

        foreach (glob($this->runsDir().'/*.json') ?: [] as $file) {
            $id = basename($file, '.json');

            if (! preg_match('/^[A-Za-z0-9_-]{1,64}$/', $id)) {
                continue;
            }

            $row = json_decode((string) @file_get_contents($file), true);

            if (! is_array($row) || ! isset($row['id'])) {
                $unreadable[$id] = true;

                continue;
            }

            $runs[] = Run::fromArray($row);
        }

        usort($runs, fn (Run $a, Run $b) => $b->startedAt <=> $a->startedAt);

        return [$runs, $unreadable];
    }

    /**
     * The trash names a run's journal recorded.
     *
     * @return list<string>
     */
    private function references(string $id, bool $mustRead): array
    {
        if (! preg_match('/^[A-Za-z0-9_-]{1,64}$/', $id)) {
            return [];
        }

        $path = $this->runsDir().'/'.$id.'/journal.jsonl';

        if (! is_file($path)) {
            return [];
        }

        try {
            if (! is_readable($path)) {
                throw new RuntimeException(Lang::trans('prune.not_readable', ['path' => $path]));
            }

            $entries = (new Journal($path))->entries();
        } catch (Throwable $e) {
            if ($mustRead) {
                throw new RuntimeException(
                    Lang::trans('prune.journal_unreadable', ['id' => $id]),
                    0,
                    $e
                );
            }

            return [];
        }

        $names = [];

        foreach ($entries as $entry) {
            if (isset($entry['trash']) && is_string($entry['trash'])) {
                $names[] = $entry['trash'];
            } elseif (isset($entry['change']) && is_array($entry['change'])) {
                try {
                    $names[] = Change::fromArray($entry['change'])->trashName();
                } catch (Throwable) {
                    // An entry that does not describe a change names nothing.
                }
            }
        }

        return $names;
    }

    /** Bytes, for people. */
    public static function human(int $bytes): string
    {
        foreach (['GB' => 1 << 30, 'MB' => 1 << 20, 'KB' => 1 << 10] as $unit => $size) {
            if ($bytes >= $size) {
                return round($bytes / $size, 1).' '.$unit;
            }
        }

        return $bytes.' B';
    }
}
