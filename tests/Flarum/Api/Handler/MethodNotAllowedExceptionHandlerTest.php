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
use Flarum\Api\Handler\MethodNotAllowedExceptionHandler;
use Flarum\Http\Exception\MethodNotAllowedException;
use Tests\Test\TestCase;

class MethodNotAllowedExceptionHandlerTest extends TestCase
{
    private $handler;

    public function init()
    {
        $this->handler = new MethodNotAllowedExceptionHandler();
    }

    public function test_it_handles_recognisable_exceptions()
    {
        $this->assertFalse($this->handler->manages(new Exception));
        $this->assertTrue($this->handler->manages(new MethodNotAllowedException()));
    }

    public function test_managing_exceptions()
    {
        $response = $this->handler->handle(new MethodNotAllowedException);

        $this->assertEquals(405, $response->getStatus());
        $this->assertEquals([
            [
                'status' => '405',
                'code' => 'method_not_allowed'
            ]
        ], $response->getErrors());
    }
}
