<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler;

use Illuminate\Database\ConnectionInterface;

/**
 * Records asset revisions one row at a time, so several instances can record
 * different assets at once.
 *
 * {@see FileVersioner} keeps the whole map in a single JSON value, which means
 * every write is a read-modify-write of all of it: a writer carrying a snapshot
 * taken before someone else's write silently erases that write when it saves.
 * Measured across two ECS tasks writing twenty assets each, half the writes
 * were lost; across six local processes, 83% were. Because
 * {@see RevisionCompiler::getUrl()} only recompiles when a revision is
 * *missing*, a lost revision is not noticed — it simply stays wrong until the
 * next admin action.
 *
 * Here each revision is its own row, keyed by the asset's path, so a write
 * touches nothing else and there is no shared value to clobber.
 *
 * Reads are memoised for the lifetime of the instance, matching FileVersioner:
 * rendering a page reads the map around thirty times and individual revisions
 * around sixty-five times through one shared instance, and every one of those
 * would otherwise be a query.
 */
class DatabaseVersioner implements VersionerInterface
{
    public const TABLE = 'asset_revisions';

    /**
     * @var array<string, string>|null
     */
    private ?array $revisions = null;

    /**
     * Revisions recorded since deferWrites(), awaiting a single statement.
     *
     * @var array<string, string|null>|null
     */
    private ?array $deferred = null;

    public function __construct(
        protected ConnectionInterface $database
    ) {
    }

    public function putRevision(string $file, ?string $revision): void
    {
        // Writing what is already recorded must not touch the database at all.
        // JsDirectoryCompiler::pruneStaleRevisions() calls this once per stale
        // chunk and runs from getUrl() — on ordinary page renders, not only on
        // rebuilds — so a render that prunes nothing has to be free.
        //
        // Read the map without settling the buffer: allRevisions() stores
        // anything deferred before answering, which would defeat the batching
        // this method is feeding.
        if (($this->loadRevisions()[$file] ?? null) === $revision) {
            return;
        }

        // Keep the memo in step with what was just recorded, rather than
        // dropping it and paying for another read.
        if ($this->revisions !== null) {
            if ($revision === null) {
                unset($this->revisions[$file]);
            } else {
                $this->revisions[$file] = $revision;
            }
        }

        if ($this->deferred !== null) {
            $this->deferred[$file] = $revision;

            return;
        }

        $this->write([$file => $revision]);
    }

    public function deferWrites(): void
    {
        $this->deferred ??= [];
    }

    /**
     * A batch left open — a caller that never flushed, or a rebuild that threw
     * past its own flush — must still be stored. Reads are answered from the
     * memo while a batch is open, so nothing else would notice the omission
     * until the revisions were missing on the next request.
     */
    public function __destruct()
    {
        try {
            $this->flushWrites();
        } catch (\Throwable $e) {
            // Shutdown is too late to do anything useful about a failed write,
            // and throwing here would mask whatever is really going on.
        }
    }

    public function flushWrites(): void
    {
        $deferred = $this->deferred;
        $this->deferred = null;

        if (! empty($deferred)) {
            $this->write($deferred);
        }
    }

    /**
     * Store a batch: one upsert for the revisions being set, one delete for
     * those being forgotten.
     *
     * @param array<string, string|null> $revisions
     */
    private function write(array $revisions): void
    {
        $set = array_filter($revisions, fn (?string $revision) => $revision !== null);
        $forget = array_keys(array_filter($revisions, fn (?string $revision) => $revision === null));

        if ($set !== []) {
            // An upsert on the primary key, so two instances recording the same
            // asset at the same moment cannot collide, and neither disturbs any
            // other asset's row.
            $this->database->table(static::TABLE)->upsert(
                array_map(
                    fn (string $file, string $revision) => compact('file', 'revision'),
                    array_keys($set),
                    $set
                ),
                ['file'],
                ['revision']
            );
        }

        if ($forget !== []) {
            $this->database->table(static::TABLE)->whereIn('file', $forget)->delete();
        }
    }

    public function getRevision(string $file): ?string
    {
        return $this->allRevisions()[$file] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function allRevisions(): array
    {
        // Deferred writes are already reflected in the memo, so a read during a
        // batch sees them without the batch having to be stored — which is what
        // lets getUrl() read back the revision it just recorded while the batch
        // is still open.
        return $this->loadRevisions();
    }

    /**
     * The memoised map, fetched once per instance.
     *
     * Rendering a page reads the map around thirty times and individual
     * revisions around sixty-five, all through one shared instance, so every
     * one of those would otherwise be a query.
     *
     * @return array<string, string>
     */
    private function loadRevisions(): array
    {
        if ($this->revisions !== null) {
            return $this->revisions;
        }

        return $this->revisions = $this->database->table(static::TABLE)
            ->pluck('revision', 'file')
            ->all();
    }
}
