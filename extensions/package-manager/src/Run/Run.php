<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Run;

use Flarum\ExtensionManager\Lang;
/**
 * The state of one update, as a value.
 *
 * 🚨 Everything the admin screen shows comes from here, and the single rule it
 * has to obey is: **this is never ambiguous**. The previous Extension Manager's worst
 * behaviour is a task row that says `running` when nothing is running, because
 * the only thing that could have changed it died. So a run carries not just what
 * it is doing but *when it last actually moved*, which is what lets a caller
 * tell a working run from a dead one without guessing.
 *
 * Immutable: every transition returns a new Run, so a half-applied state change
 * cannot exist even in memory.
 */
final class Run
{
    public const PENDING = 'pending';
    public const RUNNING = 'running';
    public const DONE = 'done';
    public const FAILED = 'failed';
    public const ROLLBACK = 'rolled-back';

    /** In the order they happen. The driver never skips one. */
    public const PHASES = ['plan', 'fetch', 'apply', 'finalise'];

    /**
     * @param list<string> $items    what this phase has to get through
     * @param list<string> $log      human-readable, newest last
     */
    public function __construct(
        public readonly string $id,
        public readonly string $state = self::PENDING,
        public readonly string $phase = 'plan',
        public readonly array $items = [],
        public readonly int $index = 0,
        public readonly array $log = [],
        public readonly ?string $error = null,
        public readonly ?string $errorStep = null,
        public readonly int $startedAt = 0,
        public readonly int $movedAt = 0,
    ) {
    }

    public static function start(string $id, int $now): self
    {
        return new self($id, self::RUNNING, 'plan', [], 0, [Lang::trans('run.starting')], null, null, $now, $now);
    }

    /** What is being worked on right now, for the screen. */
    public function current(): ?string
    {
        return $this->items[$this->index] ?? null;
    }

    public function total(): int
    {
        return count($this->items);
    }

    public function isFinished(): bool
    {
        return in_array($this->state, [self::DONE, self::FAILED, self::ROLLBACK], true);
    }

    /**
     * A run nobody has advanced for a while.
     *
     * 🚨 Reported, never acted on automatically, and never used to silently skip
     * work. This is the exact thing the previous Extension Manager gets wrong: it refuses to
     * dispatch while a task looks busy and says nothing, so one dead run blocks
     * every later one forever. Here a stale run is surfaced to the admin with its
     * age and a button, and the decision is theirs.
     */
    public function isStale(int $now, int $after = 120): bool
    {
        return $this->state === self::RUNNING && ($now - $this->movedAt) > $after;
    }

    /** Progress as a fraction of this phase, for a bar that is honest. */
    public function fraction(): float
    {
        if ($this->total() === 0) {
            return $this->isFinished() ? 1.0 : 0.0;
        }

        return min(1.0, $this->index / $this->total());
    }

    public function withItems(array $items, int $now): self
    {
        return $this->copy(['items' => array_values($items), 'index' => 0, 'movedAt' => $now]);
    }

    public function advanced(int $now, ?string $note = null): self
    {
        return $this->copy([
            'index' => $this->index + 1,
            'movedAt' => $now,
            'log' => $note === null ? $this->log : [...$this->log, $note],
        ]);
    }

    /**
     * Still on the same item, with something to say about why.
     *
     * 🚨 The note replaces the last line rather than appending, and only when
     * it is new. A waiting step is polled every couple of seconds by three
     * different drivers, so appending would bury the run's actual history under
     * a hundred copies of "waiting" — and the log is what the admin screen
     * shows.
     */
    public function waiting(int $now, string $note): self
    {
        $log = $this->log;

        if (($log[count($log) - 1] ?? null) === $note) {
            return $this->copy(['movedAt' => $now]);
        }

        return $this->copy(['movedAt' => $now, 'log' => [...$log, $note]]);
    }

    public function enteredPhase(string $phase, int $now, ?string $note = null): self
    {
        return $this->copy([
            'phase' => $phase,
            'items' => [],
            'index' => 0,
            'movedAt' => $now,
            'log' => $note === null ? $this->log : [...$this->log, $note],
        ]);
    }

    /**
     * 🚨 A failure records WHICH STEP failed, not just that something did.
     * "Has been attempted too many times" is the anti-example: true, useless,
     * and three layers away from the cause.
     */
    public function failed(string $error, string $step, int $now): self
    {
        return $this->copy([
            'state' => self::FAILED,
            'error' => $error,
            'errorStep' => $step,
            'movedAt' => $now,
            'log' => [...$this->log, Lang::trans('run.failed_during', ['step' => $step, 'error' => $error])],
        ]);
    }

    public function finished(int $now): self
    {
        return $this->copy([
            'state' => self::DONE,
            'movedAt' => $now,
            'log' => [...$this->log, Lang::trans('run.finished')],
        ]);
    }

    public function rolledBack(int $now, array $undone): self
    {
        return $this->copy([
            'state' => self::ROLLBACK,
            'movedAt' => $now,
            'log' => [...$this->log, Lang::trans('run.rolled_back', ['undone' => implode(', ', $undone) ?: Lang::trans('run.nothing_to_undo')])],
        ]);
    }

    /**
     * Failed, and already put back.
     *
     * 🚨 One transition, not `failed()` then `rolledBack()`. Two would be two
     * saves, and a reader that loaded the run between them would see a failed
     * update sitting there with its changes apparently still in place — which
     * is the exact ambiguity this class exists to prevent.
     *
     * @param list<string> $undone
     */
    public function revertedAfter(string $why, string $step, array $undone, int $now): self
    {
        return $this->copy([
            'state' => self::ROLLBACK,
            'error' => $why,
            'errorStep' => $step,
            'movedAt' => $now,
            'log' => [
                ...$this->log,
                Lang::trans('run.failed_during', ['step' => $step, 'error' => $why]),
                Lang::trans('run.put_back', ['undone' => implode(', ', $undone) ?: Lang::trans('run.nothing_to_undo')]),
            ],
        ]);
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id, 'state' => $this->state, 'phase' => $this->phase,
            'items' => $this->items, 'index' => $this->index, 'log' => $this->log,
            'error' => $this->error, 'errorStep' => $this->errorStep,
            'startedAt' => $this->startedAt, 'movedAt' => $this->movedAt,
        ];
    }

    /** @param array<string,mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            (string) ($row['id'] ?? ''),
            (string) ($row['state'] ?? self::PENDING),
            (string) ($row['phase'] ?? 'plan'),
            (array) ($row['items'] ?? []),
            (int) ($row['index'] ?? 0),
            (array) ($row['log'] ?? []),
            isset($row['error']) ? (string) $row['error'] : null,
            isset($row['errorStep']) ? (string) $row['errorStep'] : null,
            (int) ($row['startedAt'] ?? 0),
            (int) ($row['movedAt'] ?? 0),
        );
    }

    private function copy(array $changes): self
    {
        return self::fromArray(array_merge($this->toArray(), $changes));
    }
}
