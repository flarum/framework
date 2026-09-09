<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Frontend;

use Flarum\Frontend\Assets;
use Flarum\Frontend\Compiler\JsCompiler;
use Flarum\Frontend\Compiler\JsDirectoryCompiler;
use Flarum\Frontend\Compiler\LessCompiler;
use Flarum\Frontend\Event\AssetsRecompiled;
use Flarum\Frontend\RecompileFrontendAssets;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\unit\TestCase;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;

class RecompileFrontendAssetsTest extends TestCase
{
    #[Test]
    public function flush_dispatches_assets_recompiled_once()
    {
        $css = m::mock(LessCompiler::class);
        $css->shouldReceive('flush');
        $js = m::mock(JsCompiler::class);
        $js->shouldReceive('flush');
        $jsDir = m::mock(JsDirectoryCompiler::class);
        $jsDir->shouldReceive('flush');

        $assets = m::mock(Assets::class);
        $assets->shouldReceive('makeCss')->andReturn($css);
        $assets->shouldReceive('makeJs')->andReturn($js);
        $assets->shouldReceive('makeLocaleCss')->andReturn($css);
        $assets->shouldReceive('makeLocaleJs')->andReturn($js);
        $assets->shouldReceive('makeJsDirectory')->andReturn($jsDir);

        $locales = m::mock(LocaleManager::class);
        $locales->shouldReceive('getLocales')->andReturn(['en' => 'English']);

        $dispatcher = m::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->once()->with(m::type(AssetsRecompiled::class));

        $recompile = new RecompileFrontendAssets($assets, $locales, $dispatcher);
        $recompile->flush();
    }

    /**
     * recompile() must rebuild the assets in place — commit(), never flush() —
     * so the compiled files and their manifest revisions are only ever
     * overwritten, not removed. Deleting first (the flush path) leaves a window
     * where the manifest misses keys (the asset revision token flickers, firing
     * a spurious "new version" prompt) and where a client can 404 on an asset
     * whose URL was served before the lazy rebuild ran.
     */
    #[Test]
    public function recompile_commits_in_place_and_announces_once_afterwards()
    {
        $order = [];

        $makeCompiler = function (string $class) use (&$order) {
            $compiler = m::mock($class);
            $compiler->shouldNotReceive('flush');
            $compiler->shouldReceive('commit')->andReturnUsing(function () use (&$order) {
                $order[] = 'commit';
            });

            return $compiler;
        };

        $css = $makeCompiler(LessCompiler::class);
        $js = $makeCompiler(JsCompiler::class);
        $jsDir = $makeCompiler(JsDirectoryCompiler::class);

        $assets = m::mock(Assets::class);
        $assets->shouldReceive('makeCss')->andReturn($css);
        $assets->shouldReceive('makeJs')->andReturn($js);
        $assets->shouldReceive('makeLocaleCss')->andReturn($css);
        $assets->shouldReceive('makeLocaleJs')->andReturn($js);
        $assets->shouldReceive('makeJsDirectory')->andReturn($jsDir);

        $locales = m::mock(LocaleManager::class);
        $locales->shouldReceive('getLocales')->andReturn(['en' => 'English']);

        $dispatcher = m::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->once()->with(m::type(AssetsRecompiled::class))
            ->andReturnUsing(function () use (&$order) {
                $order[] = 'event';
            });

        $recompile = new RecompileFrontendAssets($assets, $locales, $dispatcher);
        $recompile->recompile();

        // The event announces a finished rebuild: every commit must precede it,
        // so a consumer (e.g. the realtime broadcaster) reads a settled revision.
        $this->assertNotEmpty($order);
        $this->assertSame('event', end($order), 'AssetsRecompiled must fire after all commits');
        $this->assertGreaterThan(0, count(array_keys($order, 'commit', true)), 'compilers must be committed');
    }

    /**
     * A toggle only MARKS the assets dirty; the rebuild itself is deferred to
     * the next request. The toggling request booted before the extension's
     * extenders were applied, so rebuilding there would bake the OLD sources
     * (e.g. locale bundles without a newly-enabled extension's keys) into the
     * manifest — and, the revision then matching, nothing would ever rebuild
     * them. Only a later, freshly-booted request sees the new extension state.
     */
    #[Test]
    public function mark_dirty_flags_the_asset_set_without_touching_the_compilers()
    {
        $assets = m::mock(Assets::class);
        $assets->shouldReceive('getName')->andReturn('forum');
        $assets->shouldNotReceive('makeCss', 'makeJs', 'makeLocaleCss', 'makeLocaleJs', 'makeJsDirectory');

        $locales = m::mock(LocaleManager::class);
        // The translator's compiled catalogue cache is stored under a fixed name
        // per locale — it is NOT keyed by the registered resources — so after a
        // toggle it would keep serving the pre-toggle catalogue. It must be
        // cleared here, or the deferred rebuild (and the UI itself) would bake
        // stale translations.
        $locales->shouldReceive('clearCache')->once();

        $settings = m::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('set')->once()->with('assets_dirty.forum', 1);

        $dispatcher = m::mock(Dispatcher::class);
        $dispatcher->shouldNotReceive('dispatch');

        (new RecompileFrontendAssets($assets, $locales, $dispatcher, $settings))->markDirty();
    }

    #[Test]
    public function recompile_if_dirty_does_nothing_when_the_set_is_clean()
    {
        $assets = m::mock(Assets::class);
        $assets->shouldReceive('getName')->andReturn('forum');
        $assets->shouldNotReceive('makeCss', 'makeJs', 'makeLocaleCss', 'makeLocaleJs', 'makeJsDirectory');

        $locales = m::mock(LocaleManager::class);

        $settings = m::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')->with('assets_dirty.forum')->andReturn(null);
        $settings->shouldNotReceive('delete');

        $dispatcher = m::mock(Dispatcher::class);
        $dispatcher->shouldNotReceive('dispatch');

        (new RecompileFrontendAssets($assets, $locales, $dispatcher, $settings))->recompileIfDirty();
    }

    #[Test]
    public function recompile_if_dirty_rebuilds_clears_the_flag_and_announces()
    {
        $order = [];

        $makeCompiler = function (string $class) use (&$order) {
            $compiler = m::mock($class);
            $compiler->shouldNotReceive('flush');
            $compiler->shouldReceive('commit')->andReturnUsing(function () use (&$order) {
                $order[] = 'commit';
            });

            return $compiler;
        };

        $assets = m::mock(Assets::class);
        $assets->shouldReceive('getName')->andReturn('forum');
        $assets->shouldReceive('makeCss')->andReturn($makeCompiler(LessCompiler::class));
        $assets->shouldReceive('makeJs')->andReturn($makeCompiler(JsCompiler::class));
        $assets->shouldReceive('makeLocaleCss')->andReturn($makeCompiler(LessCompiler::class));
        $assets->shouldReceive('makeLocaleJs')->andReturn($makeCompiler(JsCompiler::class));
        $assets->shouldReceive('makeJsDirectory')->andReturn($makeCompiler(JsDirectoryCompiler::class));

        $locales = m::mock(LocaleManager::class);
        $locales->shouldReceive('getLocales')->andReturn(['en' => 'English']);

        $settings = m::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')->with('assets_dirty.forum')->andReturn(1);
        $settings->shouldReceive('delete')->once()->with('assets_dirty.forum')
            ->andReturnUsing(function () use (&$order) {
                $order[] = 'clear';
            });

        $dispatcher = m::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->once()->with(m::type(AssetsRecompiled::class))
            ->andReturnUsing(function () use (&$order) {
                $order[] = 'event';
            });

        (new RecompileFrontendAssets($assets, $locales, $dispatcher, $settings))->recompileIfDirty();

        $this->assertGreaterThan(0, count(array_keys($order, 'commit', true)), 'compilers must be committed');
        $this->assertContains('clear', $order, 'the dirty flag must be cleared');
        $this->assertSame('event', end($order), 'AssetsRecompiled must fire last, after the rebuild settles');
    }

    /**
     * The dirty flag is only cleared once the rebuild has finished, so for the
     * whole of that rebuild every other request still sees the set as dirty and
     * starts its own. A full rebuild is seconds — measured at ~2s on local disk
     * with two locales, and far longer on remote storage — so a busy forum runs
     * N concurrent rebuilds of the same output. An atomic lock lets one request
     * do the work; the rest carry on and serve the revision already recorded,
     * which is safe precisely because markDirty() deletes nothing.
     */
    #[Test]
    public function recompile_if_dirty_skips_the_rebuild_when_another_request_holds_the_lock()
    {
        $assets = m::mock(Assets::class);
        $assets->shouldReceive('getName')->andReturn('forum');
        $assets->shouldNotReceive('makeCss', 'makeJs', 'makeLocaleCss', 'makeLocaleJs', 'makeJsDirectory');

        $locales = m::mock(LocaleManager::class);

        $settings = m::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')->with('assets_dirty.forum')->andReturn(1);
        // The winner owns the flag: a loser must not clear it, or a rebuild
        // that then fails would leave nothing marked for the next request.
        $settings->shouldNotReceive('delete');

        $dispatcher = m::mock(Dispatcher::class);
        $dispatcher->shouldNotReceive('dispatch');

        $lock = m::mock(Lock::class);
        $lock->shouldReceive('get')->once()->andReturn(false);
        $lock->shouldNotReceive('release');

        $cache = $this->cacheWithLock($lock, 'flarum.assets.recompile.forum');

        (new RecompileFrontendAssets($assets, $locales, $dispatcher, $settings, $cache))->recompileIfDirty();
    }

    #[Test]
    public function recompile_if_dirty_releases_the_lock_once_the_rebuild_is_done()
    {
        $order = [];

        $css = m::mock(LessCompiler::class);
        $css->shouldReceive('commit')->andReturnUsing(function () use (&$order) {
            $order[] = 'commit';
        });
        $js = m::mock(JsCompiler::class);
        $js->shouldReceive('commit')->andReturnUsing(function () use (&$order) {
            $order[] = 'commit';
        });
        $jsDir = m::mock(JsDirectoryCompiler::class);
        $jsDir->shouldReceive('commit')->andReturnUsing(function () use (&$order) {
            $order[] = 'commit';
        });

        $assets = m::mock(Assets::class);
        $assets->shouldReceive('getName')->andReturn('forum');
        $assets->shouldReceive('makeCss')->andReturn($css);
        $assets->shouldReceive('makeJs')->andReturn($js);
        $assets->shouldReceive('makeLocaleCss')->andReturn($css);
        $assets->shouldReceive('makeLocaleJs')->andReturn($js);
        $assets->shouldReceive('makeJsDirectory')->andReturn($jsDir);

        $locales = m::mock(LocaleManager::class);
        $locales->shouldReceive('getLocales')->andReturn(['en' => 'English']);

        $settings = m::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')->with('assets_dirty.forum')->andReturn(1);
        $settings->shouldReceive('delete')->once()->with('assets_dirty.forum');

        $dispatcher = m::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->once()->with(m::type(AssetsRecompiled::class));

        $lock = m::mock(Lock::class);
        $lock->shouldReceive('get')->once()->andReturn(true);
        // Released even though this rebuild succeeded — and, being in a
        // finally, released just as surely when a compiler throws.
        $lock->shouldReceive('release')->once()->andReturnUsing(function () use (&$order) {
            $order[] = 'release';
        });

        $cache = $this->cacheWithLock($lock, 'flarum.assets.recompile.forum');

        (new RecompileFrontendAssets($assets, $locales, $dispatcher, $settings, $cache))->recompileIfDirty();

        $this->assertSame('release', end($order), 'the lock must outlive the rebuild');
    }

    #[Test]
    public function recompile_if_dirty_releases_the_lock_when_the_rebuild_throws()
    {
        $css = m::mock(LessCompiler::class);
        $css->shouldReceive('commit')->andThrow(new \RuntimeException('compile failed'));

        $assets = m::mock(Assets::class);
        $assets->shouldReceive('getName')->andReturn('forum');
        $assets->shouldReceive('makeCss')->andReturn($css);
        $assets->shouldReceive('makeJs')->andReturn(m::mock(JsCompiler::class));
        $assets->shouldReceive('makeLocaleCss')->andReturn($css);
        $assets->shouldReceive('makeLocaleJs')->andReturn(m::mock(JsCompiler::class));
        $assets->shouldReceive('makeJsDirectory')->andReturn(m::mock(JsDirectoryCompiler::class));

        $locales = m::mock(LocaleManager::class);
        $locales->shouldReceive('getLocales')->andReturn(['en' => 'English']);

        $settings = m::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')->with('assets_dirty.forum')->andReturn(1);
        // The flag must survive a failed rebuild so the next request retries.
        $settings->shouldNotReceive('delete');

        $dispatcher = m::mock(Dispatcher::class);
        $dispatcher->shouldNotReceive('dispatch');

        $lock = m::mock(Lock::class);
        $lock->shouldReceive('get')->once()->andReturn(true);
        $lock->shouldReceive('release')->once();

        $cache = $this->cacheWithLock($lock, 'flarum.assets.recompile.forum');

        $this->expectException(\RuntimeException::class);

        try {
            (new RecompileFrontendAssets($assets, $locales, $dispatcher, $settings, $cache))->recompileIfDirty();
        } finally {
            // Mockery verifies release() was called on tearDown.
        }
    }

    /**
     * Each asset set locks on its own key. The sets are independent, so forum
     * and common must be able to rebuild at the same time rather than queueing
     * behind one another.
     */
    #[Test]
    public function the_lock_is_scoped_to_the_asset_set()
    {
        $assets = m::mock(Assets::class);
        $assets->shouldReceive('getName')->andReturn('common');

        $locales = m::mock(LocaleManager::class);

        $settings = m::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')->with('assets_dirty.common')->andReturn(1);

        $lock = m::mock(Lock::class);
        $lock->shouldReceive('get')->once()->andReturn(false);

        $cache = $this->cacheWithLock($lock, 'flarum.assets.recompile.common');

        (new RecompileFrontendAssets($assets, $locales, m::mock(Dispatcher::class), $settings, $cache))
            ->recompileIfDirty();
    }

    /**
     * A store that cannot take locks (a custom cache driver) must still get a
     * rebuild — the same behaviour as before locking existed. Losing the
     * de-duplication is much better than never rebuilding.
     */
    #[Test]
    public function recompile_if_dirty_rebuilds_without_a_lock_when_the_store_cannot_provide_one()
    {
        $css = m::mock(LessCompiler::class);
        $css->shouldReceive('commit');
        $js = m::mock(JsCompiler::class);
        $js->shouldReceive('commit');
        $jsDir = m::mock(JsDirectoryCompiler::class);
        $jsDir->shouldReceive('commit');

        $assets = m::mock(Assets::class);
        $assets->shouldReceive('getName')->andReturn('forum');
        $assets->shouldReceive('makeCss')->andReturn($css);
        $assets->shouldReceive('makeJs')->andReturn($js);
        $assets->shouldReceive('makeLocaleCss')->andReturn($css);
        $assets->shouldReceive('makeLocaleJs')->andReturn($js);
        $assets->shouldReceive('makeJsDirectory')->andReturn($jsDir);

        $locales = m::mock(LocaleManager::class);
        $locales->shouldReceive('getLocales')->andReturn(['en' => 'English']);

        $settings = m::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')->with('assets_dirty.forum')->andReturn(1);
        $settings->shouldReceive('delete')->once()->with('assets_dirty.forum');

        $dispatcher = m::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->once()->with(m::type(AssetsRecompiled::class));

        // A Store that is NOT a LockProvider.
        $cache = m::mock(CacheRepository::class);
        $cache->shouldReceive('getStore')->andReturn(m::mock(Store::class));

        (new RecompileFrontendAssets($assets, $locales, $dispatcher, $settings, $cache))->recompileIfDirty();
    }

    /**
     * No cache passed at all — the two call sites in core both provide one, but
     * the argument is optional for backwards compatibility, and third-party
     * callers constructing this class must keep working.
     */
    #[Test]
    public function recompile_if_dirty_rebuilds_when_no_cache_is_provided()
    {
        $css = m::mock(LessCompiler::class);
        $css->shouldReceive('commit');
        $js = m::mock(JsCompiler::class);
        $js->shouldReceive('commit');
        $jsDir = m::mock(JsDirectoryCompiler::class);
        $jsDir->shouldReceive('commit');

        $assets = m::mock(Assets::class);
        $assets->shouldReceive('getName')->andReturn('forum');
        $assets->shouldReceive('makeCss')->andReturn($css);
        $assets->shouldReceive('makeJs')->andReturn($js);
        $assets->shouldReceive('makeLocaleCss')->andReturn($css);
        $assets->shouldReceive('makeLocaleJs')->andReturn($js);
        $assets->shouldReceive('makeJsDirectory')->andReturn($jsDir);

        $locales = m::mock(LocaleManager::class);
        $locales->shouldReceive('getLocales')->andReturn(['en' => 'English']);

        $settings = m::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')->with('assets_dirty.forum')->andReturn(1);
        $settings->shouldReceive('delete')->once()->with('assets_dirty.forum');

        $dispatcher = m::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')->once()->with(m::type(AssetsRecompiled::class));

        (new RecompileFrontendAssets($assets, $locales, $dispatcher, $settings))->recompileIfDirty();
    }

    /**
     * A request that waited on the lock and then acquired it — because the
     * winner had just finished and released — must not rebuild the same output
     * again. The flag is re-read after acquiring, not just before.
     */
    #[Test]
    public function recompile_if_dirty_rechecks_the_flag_after_acquiring_the_lock()
    {
        $assets = m::mock(Assets::class);
        $assets->shouldReceive('getName')->andReturn('forum');
        $assets->shouldNotReceive('makeCss', 'makeJs', 'makeLocaleCss', 'makeLocaleJs', 'makeJsDirectory');

        $locales = m::mock(LocaleManager::class);

        $settings = m::mock(SettingsRepositoryInterface::class);
        // Dirty on the first read, clean by the time the lock is held.
        $settings->shouldReceive('get')->with('assets_dirty.forum')->twice()
            ->andReturn(1, null);
        $settings->shouldNotReceive('delete');

        $dispatcher = m::mock(Dispatcher::class);
        $dispatcher->shouldNotReceive('dispatch');

        $lock = m::mock(Lock::class);
        $lock->shouldReceive('get')->once()->andReturn(true);
        $lock->shouldReceive('release')->once();

        $cache = $this->cacheWithLock($lock, 'flarum.assets.recompile.forum');

        (new RecompileFrontendAssets($assets, $locales, $dispatcher, $settings, $cache))->recompileIfDirty();
    }

    /**
     * The TTL is the longest anything can go un-rebuilt after a process is
     * killed mid-rebuild, since a dead holder never releases. Keep it close
     * enough to a real rebuild that a crash costs a minute, not five.
     */
    #[Test]
    public function the_rebuild_lock_expires_soon_enough_to_recover_from_a_crash()
    {
        $this->assertLessThanOrEqual(
            60,
            RecompileFrontendAssets::REBUILD_LOCK_SECONDS,
            'a longer lease is a longer stall after a crashed rebuild'
        );
    }

    private function cacheWithLock(m\MockInterface $lock, string $expectedKey): CacheRepository
    {
        $store = m::mock(Store::class, LockProvider::class);
        $store->shouldReceive('lock')->once()->with($expectedKey, m::type('int'))->andReturn($lock);

        /** @var CacheRepository&m\MockInterface $cache */
        $cache = m::mock(CacheRepository::class);
        $cache->shouldReceive('getStore')->andReturn($store);

        return $cache;
    }
}
