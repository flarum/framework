<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Frontend;

use Flarum\Frontend\AssetManager;
use Flarum\Frontend\Assets;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\unit\TestCase;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;

class AssetManagerTest extends TestCase
{
    #[Test]
    public function it_resolves_each_registered_frontend_by_name()
    {
        $forum = m::mock(Assets::class);
        $admin = m::mock(Assets::class);
        $common = m::mock(Assets::class);

        $container = m::mock(Container::class);
        $container->shouldReceive('make')->with('flarum.assets.forum')->andReturn($forum);
        $container->shouldReceive('make')->with('flarum.assets.admin')->andReturn($admin);
        $container->shouldReceive('make')->with('flarum.assets.common')->andReturn($common);

        $manager = new AssetManager($container, m::mock(LocaleManager::class));

        // Exactly what core registers: Forum/Admin/FrontendServiceProvider each
        // map a frontend NAME to a container ABSTRACT.
        $manager->register('forum', 'flarum.assets.forum');
        $manager->register('admin', 'flarum.assets.admin');
        $manager->register('common', 'flarum.assets.common');

        $this->assertSame($forum, $manager->frontend('forum'));
        $this->assertSame($admin, $manager->frontend('admin'));
        $this->assertSame($common, $manager->frontend('common'));
    }

    #[Test]
    public function it_resolves_a_frontend_registered_by_an_extension()
    {
        $assets = m::mock(Assets::class);

        $container = m::mock(Container::class);
        $container->shouldReceive('make')->with('flarum.assets.support')->andReturn($assets);

        $manager = new AssetManager($container, m::mock(LocaleManager::class));
        $manager->register('support', 'flarum.assets.support');

        $this->assertSame($assets, $manager->frontend('support'));
    }

    #[Test]
    public function it_rejects_an_unregistered_frontend()
    {
        $container = m::mock(Container::class);
        $container->shouldNotReceive('make');

        $manager = new AssetManager($container, m::mock(LocaleManager::class));
        $manager->register('forum', 'flarum.assets.forum');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown frontend: nope');

        $manager->frontend('nope');
    }

    #[Test]
    public function it_does_not_mistake_a_container_abstract_for_a_frontend_name()
    {
        // The registry is keyed by frontend name, and the abstract is the VALUE.
        // Looking a name up against the values would both reject every real
        // frontend and accept an abstract as though it were one.
        $container = m::mock(Container::class);
        $container->shouldNotReceive('make');

        $manager = new AssetManager($container, m::mock(LocaleManager::class));
        $manager->register('forum', 'flarum.assets.forum');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown frontend: flarum.assets.forum');

        $manager->frontend('flarum.assets.forum');
    }

    #[Test]
    public function all_returns_every_registered_frontend_keyed_by_name()
    {
        $forum = m::mock(Assets::class);
        $admin = m::mock(Assets::class);

        $container = m::mock(Container::class);
        $container->shouldReceive('make')->with('flarum.assets.forum')->andReturn($forum);
        $container->shouldReceive('make')->with('flarum.assets.admin')->andReturn($admin);

        $manager = new AssetManager($container, m::mock(LocaleManager::class));
        $manager->register('forum', 'flarum.assets.forum');
        $manager->register('admin', 'flarum.assets.admin');

        $this->assertSame(['forum' => $forum, 'admin' => $admin], $manager->all());
    }

    /**
     * The settings that trigger this — maintenance_mode, safe_mode_extensions
     * — decide which extensions boot, so the compiled bundles have to be
     * rebuilt when one changes.
     *
     * Flagging, rather than deleting the files as this used to: deleting them
     * meant the only request that could rebuild during safe mode was an
     * admin's (they alone get past CheckForMaintenanceMode), so the reduced
     * safe-mode bundle was recorded as the canonical revision with nothing
     * left to mark it stale. The forum then served that cut-down JS to
     * everyone, even after safe mode ended. Flagging leaves the file and its
     * revision alone, so leaving safe mode flags the sets again and the next
     * normal request restores the full bundle.
     */
    #[Test]
    public function mark_dirty_flags_every_registered_frontend_without_touching_the_compilers()
    {
        $flagged = [];

        $makeAssets = function (string $name) {
            $assets = m::mock(Assets::class);
            $assets->shouldReceive('getName')->andReturn($name);
            // Nothing may be compiled or deleted here.
            $assets->shouldNotReceive('makeCss', 'makeJs', 'makeLocaleCss', 'makeLocaleJs', 'makeJsDirectory');

            return $assets;
        };

        $container = m::mock(Container::class);
        $container->shouldReceive('make')->with('flarum.assets.forum')->andReturn($makeAssets('forum'));
        $container->shouldReceive('make')->with('flarum.assets.admin')->andReturn($makeAssets('admin'));
        $container->shouldReceive('make')->with('events')->andReturn(m::mock(Dispatcher::class));

        $settings = m::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('set')->andReturnUsing(function (string $key) use (&$flagged) {
            $flagged[] = $key;
        });
        $settings->shouldNotReceive('delete');
        $container->shouldReceive('make')->with(SettingsRepositoryInterface::class)->andReturn($settings);

        $locales = m::mock(LocaleManager::class);
        $locales->shouldReceive('clearCache');

        $manager = new AssetManager($container, $locales);
        $manager->register('forum', 'flarum.assets.forum');
        $manager->register('admin', 'flarum.assets.admin');

        $manager->markDirty();

        $this->assertEquals(['assets_dirty.forum', 'assets_dirty.admin'], $flagged);
    }
}
