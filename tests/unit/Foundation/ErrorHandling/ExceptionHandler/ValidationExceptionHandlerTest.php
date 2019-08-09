<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Foundation\ErrorHandling\ExceptionHandler;

use Flarum\Foundation\ErrorHandling\ExceptionHandler\ValidationExceptionHandler;
use Flarum\Foundation\ValidationException;
use PHPUnit\Framework\TestCase;

class ValidationExceptionHandlerTest extends TestCase
{
    private $handler;

    public function setUp()
    {
        $this->handler = new ValidationExceptionHandler;
    }

    public function test_managing_exceptions()
    {
        $error = $this->handler->handle(new ValidationException(
            ['foo' => 'Attribute error'],
            ['bar' => 'Relationship error']
        ));

        $this->assertEquals(422, $error->getStatusCode());
        $this->assertEquals('validation_error', $error->getType());
        $this->assertEquals([
            [
                'detail' => 'Attribute error',
                'source' => ['pointer' => '/data/attributes/foo']
            ],
            [
                'detail' => 'Relationship error',
                'source' => ['pointer' => '/data/relationships/bar']
            ]
        ], $error->getDetails());
    }
}
