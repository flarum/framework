<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Flarum\Api\Handler;

use Exception;
use Flarum\Api\Handler\RouteNotFoundExceptionHandler;
use Flarum\Http\Exception\RouteNotFoundException;
use Tests\Test\TestCase;

class RouteNotFoundExceptionHandlerTest extends TestCase
{
    private $handler;

    public function init()
    {
        $this->handler = new RouteNotFoundExceptionHandler();
    }

    public function test_it_handles_recognisable_exceptions()
    {
        $this->assertFalse($this->handler->manages(new Exception));
        $this->assertTrue($this->handler->manages(new RouteNotFoundException()));
    }

    public function test_managing_exceptions()
    {
        $response = $this->handler->handle(new RouteNotFoundException);

        $this->assertEquals(404, $response->getStatus());
        $this->assertEquals([
            [
                'status' => '404',
                'code' => 'route_not_found'
            ]
        ], $response->getErrors());
    }
}
