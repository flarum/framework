<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Api\ExceptionHandler;

use Exception;
use Flarum\Api\ExceptionHandler\FloodingExceptionHandler;
use Flarum\Post\Exception\FloodingException;
use PHPUnit\Framework\TestCase;

class FloodingExceptionHandlerTest extends TestCase
{
    private $handler;

    public function setUp()
    {
        $this->handler = new FloodingExceptionHandler;
    }

    public function test_it_handles_recognisable_exceptions()
    {
        $this->assertFalse($this->handler->manages(new Exception));
        $this->assertTrue($this->handler->manages(new FloodingException));
    }

    public function test_it_provides_expected_output()
    {
        $result = $this->handler->handle(new FloodingException);

        $this->assertEquals(429, $result->getStatus());
        $this->assertEquals([
            [
                'status' => '429',
                'code' => 'too_many_requests'
            ]
        ], $result->getErrors());
    }
}
