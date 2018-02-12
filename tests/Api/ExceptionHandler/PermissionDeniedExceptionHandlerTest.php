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
use Flarum\Api\ExceptionHandler\PermissionDeniedExceptionHandler;
use Flarum\Tests\Test\TestCase;
use Flarum\User\Exception\PermissionDeniedException;

class PermissionDeniedExceptionHandlerTest extends TestCase
{
    private $handler;

    public function init()
    {
        $this->handler = new PermissionDeniedExceptionHandler;
    }

    public function test_it_handles_recognisable_exceptions()
    {
        $this->assertFalse($this->handler->manages(new Exception));
        $this->assertTrue($this->handler->manages(new PermissionDeniedException));
    }

    public function test_managing_exceptions()
    {
        $response = $this->handler->handle(new PermissionDeniedException);

        $this->assertEquals(401, $response->getStatus());
        $this->assertEquals([
            [
                'status' => '401',
                'code' => 'permission_denied'
            ]
        ], $response->getErrors());
    }
}
