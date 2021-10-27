<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Http;

use Flarum\Http\RouteCollection;
use Flarum\Testing\unit\TestCase;

class RouteCollectionTest extends TestCase
{
    /** @test */
    public function can_add_routes()
    {
        $routeCollection = (new RouteCollection)
            ->addRoute('GET', '/index', 'index', function () {
                echo 'index';
            })
            ->addRoute('DELETE', '/posts', 'forum.posts.delete', function () {
                echo 'delete posts';
            });

        $this->assertEquals('/index', $routeCollection->getPath('index'));
        $this->assertEquals('/posts', $routeCollection->getPath('forum.posts.delete'));
    }

    /** @test */
    public function can_add_routes_late()
    {
        $routeCollection = (new RouteCollection)->addRoute('GET', '/index', 'index', function () {
            echo 'index';
        });

        $this->assertEquals('/index', $routeCollection->getPath('index'));

        $routeCollection->addRoute('DELETE', '/posts', 'forum.posts.delete', function () {
            echo 'delete posts';
        });

        $this->assertEquals('/posts', $routeCollection->getPath('forum.posts.delete'));
    }

    /** @test */
    public function must_provide_required_parameters()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Could not generate URL for route 'user': no value provided for required part 'user'.");

        $routeCollection = (new RouteCollection)->addRoute('GET', '/user/{user}', 'user', function () {
            echo 'user';
        });

        $routeCollection->getPath('user', []);
    }

    /** @test */
    public function dont_need_to_provide_optional_parameters()
    {
        $routeCollection = (new RouteCollection)->addRoute('GET', '/user/{user}[/{test}]', 'user', function () {
            echo 'user';
        });

        $path = $routeCollection->getPath('user', ['user' => 'SomeUser']);

        $this->assertEquals('/user/SomeUser', $path);
    }

    /** @test */
    public function can_provide_optional_parameters()
    {
        $routeCollection = (new RouteCollection)->addRoute('GET', '/user/{user}[/{test}]', 'user', function () {
            echo 'user';
        });

        $path = $routeCollection->getPath('user', ['user' => 'SomeUser', 'test' => 'Flarum']);

        $this->assertEquals('/user/SomeUser/Flarum', $path);
    }
}
