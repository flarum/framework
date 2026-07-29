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
}
