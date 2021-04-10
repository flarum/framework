<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Testing\integration\TestCase;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RoutesTest extends TestCase
{
    /**
     * @test
     */
    public function custom_route_does_not_exist_by_default()
    {
        $response = $this->send(
            $this->request('GET', '/custom')
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function custom_route_can_be_added_by_extender()
    {
        $this->extend(
            (new Extend\Routes('forum'))
                ->get('/custom', 'custom', CustomRoute::class)
        );

        $response = $this->send(
            $this->request('GET', '/custom')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello Flarumites!', $response->getBody());
    }

    /**
     * @test
     */
    public function existing_route_can_be_removed()
    {
        $this->extend(
            (new Extend\Routes('api'))
                ->remove('forum.show')
        );

        $response = $this->send(
            $this->request('GET', '/api')
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function custom_route_can_override_existing_route_if_removed()
    {
        $this->extend(
            (new Extend\Routes('api'))
                ->remove('forum.show')
                ->get('/', 'forum.show', CustomRoute::class)
        );

        $response = $this->send(
            $this->request('GET', '/api')
        );

        $this->assertEquals('Hello Flarumites!', $response->getBody());
    }
}

class CustomRoute implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new TextResponse('Hello Flarumites!');
    }
}
