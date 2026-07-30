<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Frontend\Content;

use Flarum\Foundation\Config;
use Flarum\Frontend\Assets as FrontendAssets;
use Flarum\Frontend\Compiler\JsCompiler;
use Flarum\Frontend\Compiler\JsDirectoryCompiler;
use Flarum\Frontend\Compiler\LessCompiler;
use Flarum\Frontend\Content\Assets;
use Flarum\Frontend\Document;
use Flarum\Testing\unit\TestCase;
use Illuminate\Contracts\Container\Container;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;

class AssetsTest extends TestCase
{
    /**
     * Debug mode recompiles the assets on every request so changes show up
     * immediately — but it must do so with a plain commit(), which only writes
     * when the output actually changed. Passing force would rewrite every
     * bundle, sourcemap and the revision manifest on every single page view,
     * even when byte-identical. force is the trust-nothing repair path, not a
     * per-request tool.
     */
    #[Test]
    public function debug_mode_recompiles_without_forcing_writes()
    {
        $commitArgs = [];

        $makeCompiler = function (string $class) use (&$commitArgs) {
            $compiler = m::mock($class);
            $compiler->shouldReceive('commit')->andReturnUsing(function (...$args) use (&$commitArgs) {
                $commitArgs[] = $args;
            });
            $compiler->shouldReceive('getUrl')->andReturn(null);

            return $compiler;
        };

        $frontendAssets = m::mock(FrontendAssets::class);
        $frontendAssets->shouldReceive('makeJs')->andReturn($makeCompiler(JsCompiler::class));
        $frontendAssets->shouldReceive('makeLocaleJs')->andReturn($makeCompiler(JsCompiler::class));
        $frontendAssets->shouldReceive('makeJsDirectory')->andReturn($makeCompiler(JsDirectoryCompiler::class));
        $frontendAssets->shouldReceive('makeCss')->andReturn($makeCompiler(LessCompiler::class));
        $frontendAssets->shouldReceive('makeLocaleCss')->andReturn($makeCompiler(LessCompiler::class));

        $commonAssets = m::mock(FrontendAssets::class);
        $commonAssets->shouldReceive('makeJsDirectory')->andReturn($makeCompiler(JsDirectoryCompiler::class));

        $container = m::mock(Container::class);
        $container->shouldReceive('make')->with('flarum.assets.forum')->andReturn($frontendAssets);
        $container->shouldReceive('make')->with('flarum.assets.common')->andReturn($commonAssets);

        // Config is a readonly class and can't be mocked — use the real thing.
        $config = new Config(['url' => 'http://localhost', 'debug' => true]);

        /** @var Assets&m\MockInterface $content */
        $content = m::mock(Assets::class.'[recompileIfDirty,addAssetsToDocument]', [$container, $config])
            ->shouldAllowMockingProtectedMethods();
        $content->shouldReceive('recompileIfDirty');
        $content->shouldReceive('addAssetsToDocument');

        $request = m::mock(ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with('locale')->andReturn('en');

        $content->forFrontend('forum');
        $content(m::mock(Document::class), $request);

        $this->assertNotEmpty($commitArgs, 'debug mode must recompile the assets');

        foreach ($commitArgs as $args) {
            $this->assertNotSame(true, $args[0] ?? false, 'debug recompiles must not force writes of unchanged output');
        }
    }
}
