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
use Flarum\Api\ExceptionHandler\ValidationExceptionHandler;
use Flarum\Foundation\ValidationException;
use Flarum\Tests\Test\TestCase;

class ValidationExceptionHandlerTest extends TestCase
{
    private $handler;

    public function init()
    {
        $this->handler = new ValidationExceptionHandler;
    }

    public function test_it_handles_recognisable_exceptions()
    {
        $this->assertFalse($this->handler->manages(new Exception));
        $this->assertTrue($this->handler->manages(new ValidationException([])));
    }

    public function test_managing_exceptions()
    {
        $response = $this->handler->handle(new ValidationException(
            ['foo' => 'Attribute error'],
            ['bar' => 'Relationship error']
        ));

        $this->assertEquals(422, $response->getStatus());
        $this->assertEquals([
            [
                'status' => '422',
                'code' => 'validation_error',
                'detail' => 'Attribute error',
                'source' => ['pointer' => '/data/attributes/foo']
            ],
            [
                'status' => '422',
                'code' => 'validation_error',
                'detail' => 'Relationship error',
                'source' => ['pointer' => '/data/relationships/bar']
            ]
        ], $response->getErrors());
    }
}
