<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Frontend\Event\AssetsRecompiled;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Events\Dispatcher;
use LogicException;

/**
 * @internal
 */
class RecompileFrontendAssets
{
    /**
     * How long one request may hold the rebuild lock.
     *
     * A process killed mid-rebuild — OOM, an evicted container, a deploy —
     * never releases the lock, so this is also the longest anything can go
     * un-rebuilt after a crash. Sixty seconds is roughly twice the slowest
     * full rebuild measured against remote storage, where every compiled file
     * costs a network round trip.
     *
     * Both failure modes degrade to the behaviour that existed before this
     * lock, never to anything worse: the dirty flag outlives the lock, so
     * after a crash the next request past the TTL rebuilds, and a rebuild that
     * legitimately outruns the TTL simply lets a second request start one.
     */
    public const REBUILD_LOCK_SECONDS = 60;

    public function __construct(
        protected Assets $assets,
        protected LocaleManager $locales,
        protected ?Dispatcher $events = null,
        protected ?SettingsRepositoryInterface $settings = null,
        protected ?CacheRepository $cache = null
    ) {
    }

    /**
     * Flag this asset set as needing a rebuild, without touching the compiled
     * files or their manifest revisions.
     *
     * The rebuild itself is deferred to the next request (see
     * {@see recompileIfDirty}): a request that toggles an extension booted
     * before that extension's extenders were applied, so rebuilding here would
     * bake the old sources — e.g. locale bundles without a newly-enabled
     * extension's keys — into the manifest, and with the revision then
     * matching, nothing would ever rebuild them. Deferring also means nothing
     * is deleted or unset in the meantime: already-served asset URLs keep
     * resolving, and the asset revision token doesn't flicker, so connected
     * clients aren't prompted to reload for a rebuild that may not change
     * anything they load.
     */
    public function markDirty(): void
    {
        $this->settings()->set($this->dirtyKey(), 1);

        // The translator's compiled catalogue cache is stored under a fixed
        // name per locale — it is NOT keyed by the registered resources — so
        // without this, requests after a toggle would keep loading the
        // pre-toggle catalogue, and the deferred rebuild (and the UI itself)
        // would bake stale translations: a newly-enabled extension's keys
        // would be missing until a manual cache clear.
        $this->locales->clearCache();
    }

    /**
     * Rebuild this asset set in place if it has been flagged dirty, then clear
     * the flag. Runs early in a freshly-booted request — one whose container
     * reflects the current extension state — so the rebuilt output is correct.
     */
    public function recompileIfDirty(): void
    {
        if (! $this->settings()->get($this->dirtyKey())) {
            return;
        }

        $store = $this->cache?->getStore();

        if (! $store instanceof LockProvider) {
            // No atomic lock available (a custom cache driver). Rebuild
            // unguarded, exactly as before locking existed: losing the
            // de-duplication is far better than never rebuilding.
            $this->rebuildAndClear();

            return;
        }

        // The flag is only cleared once the rebuild has finished, so without a
        // lock every request arriving during it — page renders and API calls
        // alike — sees the set as dirty and starts its own rebuild of the same
        // output. A full rebuild is seconds, so a busy forum runs a stampede of
        // them. One request does the work; the rest return and serve the
        // revision already recorded, which stays valid because markDirty()
        // deletes neither the compiled files nor their revisions.
        $lock = $store->lock($this->rebuildLockKey(), static::REBUILD_LOCK_SECONDS);

        if (! $lock->get()) {
            return;
        }

        try {
            // Re-read the flag now the lock is held: a request that queued
            // behind the winner would otherwise immediately rebuild the very
            // output that winner just committed.
            if (! $this->settings()->get($this->dirtyKey())) {
                return;
            }

            $this->rebuildAndClear();
        } finally {
            $lock->release();
        }
    }

    /**
     * Rebuild the set, clear the flag, then announce.
     *
     * The flag is cleared before announcing: if the process dies mid-rebuild
     * the flag survives and the next request simply rebuilds again (a cheap
     * no-op when the output already matches), while the event only ever fires
     * once everything — including the bookkeeping — has settled.
     */
    protected function rebuildAndClear(): void
    {
        $this->commitAll();

        $this->settings()->delete($this->dirtyKey());

        $this->events?->dispatch(new AssetsRecompiled());
    }

    /**
     * Scoped to this asset set: the sets are independent, so forum, admin and
     * common rebuild concurrently rather than queueing behind one another.
     */
    protected function rebuildLockKey(): string
    {
        return 'flarum.assets.recompile.'.$this->assets->getName();
    }

    protected function dirtyKey(): string
    {
        return 'assets_dirty.'.$this->assets->getName();
    }

    protected function settings(): SettingsRepositoryInterface
    {
        return $this->settings ?? throw new LogicException(
            'A '.SettingsRepositoryInterface::class.' must be provided to use the dirty-marking methods.'
        );
    }

    /**
     * Rebuild the compiled assets in place.
     *
     * Unlike {@see flush}, nothing is deleted up front: each compiler renders
     * its output and only overwrites the file (and its manifest revision) when
     * the result actually differs. That means there is never a window where an
     * already-served asset URL points at a missing file, and never a gap in the
     * revision manifest that would flicker the asset revision token and fire a
     * spurious "new version available" prompt on connected clients. A rebuild
     * that produces identical output is a complete no-op.
     *
     * {@see AssetsRecompiled} is dispatched once the rebuild has finished, so
     * consumers (e.g. the realtime broadcaster) read a settled revision.
     */
    public function recompile(): void
    {
        $this->commitAll();

        $this->events?->dispatch(new AssetsRecompiled());
    }

    protected function commitAll(): void
    {
        $this->assets->makeCss()->commit();
        $this->assets->makeJs()->commit();

        foreach ($this->locales->getLocales() as $locale => $name) {
            $this->assets->makeLocaleCss($locale)->commit();
            $this->assets->makeLocaleJs($locale)->commit();
        }

        $this->assets->makeJsDirectory()->commit();
    }

    public function flush(): void
    {
        $this->flushCss();
        $this->flushJs();

        $this->events?->dispatch(new AssetsRecompiled());
    }

    protected function flushCss(): void
    {
        $this->assets->makeCss()->flush();

        foreach ($this->locales->getLocales() as $locale => $name) {
            $this->assets->makeLocaleCss($locale)->flush();
        }
    }

    protected function flushJs(): void
    {
        $this->assets->makeJs()->flush();

        foreach ($this->locales->getLocales() as $locale => $name) {
            $this->assets->makeLocaleJs($locale)->flush();
        }

        $this->assets->makeJsDirectory()->flush();
    }
}
