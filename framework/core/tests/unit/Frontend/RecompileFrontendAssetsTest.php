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
}
