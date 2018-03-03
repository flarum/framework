<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\Api\ExceptionHandler;

use Exception;
use Flarum\Api\Exception\InvalidAccessTokenException;
use Flarum\Api\ExceptionHandler\InvalidAccessTokenExceptionHandler;
use Flarum\Tests\Test\TestCase;

class InvalidAccessTokenExceptionHandlerTest extends TestCase
{
    private $handler;

    public function init()
    {
        $this->handler = new InvalidAccessTokenExceptionHandler;
    }

    public function test_it_handles_recognisable_exceptions()
    {
        $this->assertFalse($this->handler->manages(new Exception));
        $this->assertTrue($this->handler->manages(new InvalidAccessTokenException));
    }

    public function test_output()
    {
        $response = $this->handler->handle(new InvalidAccessTokenException);

        $this->assertEquals(401, $response->getStatus());
        $this->assertEquals([
            [
                'status' => '401',
                'code' => 'invalid_access_token'
            ]
        ], $response->getErrors());
    }
}
