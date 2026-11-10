<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler;

/**
 * Records which revision of each compiled asset is current, so that a URL can
 * be cache-busted and a client can tell when what it loaded was superseded.
 *
 * Implementations are swappable — pointing the assets disk at S3 or sharing it
 * between instances are both reasons to store revisions somewhere other than
 * beside the files. What follows is what the compilers rely on, and what any
 * replacement therefore has to provide.
 *
 * **A write must not disturb another file's revision.** Several instances
 * record revisions at once — every one of them rebuilds a dirty asset set on
 * its next request — and {@see JsDirectoryCompiler::pruneStaleRevisions()}
 * writes once per stale chunk from `getUrl()`, so writes happen during ordinary
 * page renders too. An implementation that saves the whole map back on every
 * write loses whichever revisions were recorded since it last read: the writes
 * report success, and because {@see RevisionCompiler::getUrl()} only recompiles
 * when a revision is *missing*, nothing notices the loss until the next admin
 * action. Store each revision independently, or serialise the read-modify-write.
 *
 * **Writing an unchanged value must not write at all.** Pruning runs on
 * renders, so a render that prunes nothing has to cost nothing.
 *
 * **Errors must propagate.** A read that fails but reports "no revision" makes
 * `getUrl()` recompile on every request and then return null, dropping the
 * stylesheet or bundle from the page; a write that fails but reports success
 * leaves the old revision in place with nothing to trigger a retry.
 *
 * **Reads may be memoised for the lifetime of the instance, and no longer.**
 * Rendering a page reads the map around thirty times and individual revisions
 * around sixty-five, all through one shared instance, so caching within a
 * request is expected. Caching beyond it would serve revisions that another
 * instance has since replaced. A write must leave the memo agreeing with
 * storage, so `getRevision()` returns what was just written.
 *
 * **`allRevisions()` must agree with `getRevision()`** for every file it
 * contains: the map is inlined into the page for the client, and hashed into
 * the asset revision token, so a disagreement shows up as a spurious reload
 * prompt or a chunk the client cannot resolve.
 */
interface VersionerInterface
{
    /**
     * Record the current revision of a file, or forget it when null.
     */
    public function putRevision(string $file, ?string $revision): void;

    /**
     * Collect writes until {@see flushWrites()} instead of storing each one as
     * it arrives, so a rebuild that records every asset in turn can store them
     * together.
     *
     * Buffered writes are still visible to this instance's own reads, and a
     * read of anything buffered stores the buffer first, so nothing can observe
     * a revision that has been recorded but not saved. An implementation with
     * nothing to gain from batching may treat both methods as no-ops.
     */
    public function deferWrites(): void;

    /**
     * Store anything {@see deferWrites()} collected, and stop deferring.
     *
     * Safe to call when nothing was deferred.
     */
    public function flushWrites(): void;

    /**
     * The recorded revision, or null when there is none.
     */
    public function getRevision(string $file): ?string;

    /**
     * Every recorded revision, keyed by file.
     *
     * @return array<string, string>
     */
    public function allRevisions(): array;
}
