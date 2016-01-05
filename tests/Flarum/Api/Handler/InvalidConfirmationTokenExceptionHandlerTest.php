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
use Flarum\Api\Handler\InvalidConfirmationTokenExceptionHandler;
use Flarum\Core\Exception\InvalidConfirmationTokenException;
use Tests\Test\TestCase;

class InvalidConfirmationTokenExceptionHandlerTest extends TestCase
{
    private $handler;

    public function init()
    {
        $this->handler = new InvalidConfirmationTokenExceptionHandler;
    }

    public function test_it_handles_recognisable_exceptions()
    {
        $this->assertFalse($this->handler->manages(new Exception));
        $this->assertTrue($this->handler->manages(new InvalidConfirmationTokenException));
    }

    public function test_output()
    {
        $response = $this->handler->handle(new InvalidConfirmationTokenException);

        $this->assertEquals(403, $response->getStatus());
        $this->assertEquals([
            [
                'status' => '403',
                'code' => 'invalid_confirmation_token'
            ]
        ], $response->getErrors());
    }
}
