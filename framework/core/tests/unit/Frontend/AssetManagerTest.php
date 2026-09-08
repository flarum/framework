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
use Flarum\Testing\unit\TestCase;
use Illuminate\Contracts\Container\Container;
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
}
