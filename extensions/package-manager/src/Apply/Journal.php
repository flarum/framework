<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Apply;

use Flarum\ExtensionManager\Lang;
use RuntimeException;

/**
 * An append-only record of what an apply has done, and what it was about to do.
 *
 * 🚨 This is the whole safety story. Everything else in the Extension Manager is
 * convenience; if this file is wrong, an interrupted update is unrecoverable and
 * the product has no reason to exist.
 *
 * Two properties it has to have:
 *
 * 1. **Written before the act, not after.** Each change is recorded as `begun`
 *    and flushed to disk BEFORE the filesystem is touched, then marked `done`
 *    once it is. A crash therefore leaves evidence of an intention, which is
 *    recoverable; a journal written afterwards would leave a mutated tree with
 *    no record, which is not.
 *
 * 2. **Append-only, one JSON object per line.** A half-written final line is
 *    expected — that is what a killed process leaves — so reading tolerates it
 *    and discards it. A single JSON document would be corrupt in that situation
 *    and take the whole record with it.
 *
 * The sequence numbers are per-entry and monotonic, so replaying backwards is
 * just reading the lines in reverse.
 */
class Journal
{
    private const STATE_BEGUN = 'begun';
    private const STATE_DONE = 'done';

    public function __construct(private string $path)
    {
    }

    public function path(): string
    {
        return $this->path;
    }

    public function exists(): bool
    {
        return is_file($this->path);
    }

    /**
     * Record an intention and return its sequence number.
     *
     * Returns only once the line is on disk — see write().
     */
    public function begin(array $entry): int
    {
        $seq = $this->nextSeq();

        $this->write(['seq' => $seq, 'state' => self::STATE_BEGUN] + $entry);

        return $seq;
    }

    /** Record that the intention at $seq was carried out. */
    public function complete(int $seq): void
    {
        $this->write(['seq' => $seq, 'state' => self::STATE_DONE]);
    }

    /**
     * Every entry, oldest first, folded so each sequence number appears once
     * with its final state.
     *
     * @return list<array<string,mixed>>
     */
    public function entries(): array
    {
        if (! $this->exists()) {
            return [];
        }

        $handle = fopen($this->path, 'rb');

        if ($handle === false) {
            throw new RuntimeException(Lang::trans('journal.read_failed', ['path' => $this->path]));
        }

        $bySeq = [];
        $order = [];

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $row = json_decode($line, true);

            /*
             * 🚨 A malformed final line is NORMAL, not corruption: it is what a
             * process killed mid-write leaves behind. Discarding it is correct —
             * the action it described had not been recorded as begun, so it had
             * not started either.
             */
            if (! is_array($row) || ! isset($row['seq'])) {
                continue;
            }

            $seq = (int) $row['seq'];

            if (! isset($bySeq[$seq])) {
                $bySeq[$seq] = $row;
                $order[] = $seq;
            } else {
                $bySeq[$seq] = array_merge($bySeq[$seq], $row);
            }
        }

        fclose($handle);

        return array_values(array_map(fn (int $s) => $bySeq[$s], $order));
    }

    /**
     * Entries that were begun and never completed.
     *
     * At most one of these can exist in a journal written by a single applier,
     * and it is the change that was in flight when the process died — the only
     * one whose on-disk state has to be established by looking rather than by
     * trusting the record.
     *
     * @return list<array<string,mixed>>
     */
    public function interrupted(): array
    {
        return array_values(array_filter(
            $this->entries(),
            fn (array $e) => ($e['state'] ?? null) !== self::STATE_DONE
        ));
    }

    public function isComplete(): bool
    {
        return $this->entries() !== [] && $this->interrupted() === [];
    }

    public function discard(): void
    {
        if ($this->exists()) {
            unlink($this->path);
        }
    }

    private function nextSeq(): int
    {
        $entries = $this->entries();

        return $entries === [] ? 1 : (int) end($entries)['seq'] + 1;
    }

    /**
     * 🚨 Flushed AND fsync'd before returning.
     *
     * Without the fsync the line sits in the OS page cache, and a power loss or
     * container kill loses the record while keeping the filesystem change it was
     * supposed to describe — which is precisely the situation this class exists
     * to make impossible. The cost is one sync per package, against an operation
     * that is already doing disk I/O.
     */
    private function write(array $row): void
    {
        $dir = dirname($this->path);

        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException(Lang::trans('journal.dir_failed', ['path' => $dir]));
        }

        $handle = fopen($this->path, 'ab');

        if ($handle === false) {
            throw new RuntimeException(Lang::trans('journal.write_failed', ['path' => $this->path]));
        }

        $line = json_encode($row, JSON_UNESCAPED_SLASHES)."\n";

        if (fwrite($handle, $line) === false) {
            fclose($handle);

            throw new RuntimeException(Lang::trans('journal.append_failed', ['path' => $this->path]));
        }

        fflush($handle);
        fsync($handle);
        fclose($handle);
    }
}
