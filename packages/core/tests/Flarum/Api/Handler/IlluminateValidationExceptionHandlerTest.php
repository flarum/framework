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
use Flarum\Api\Handler\ValidationExceptionHandler;
use Flarum\Core\Exception\ValidationException;
use Tests\Test\TestCase;

class IlluminateValidationExceptionHandlerTest extends TestCase
{
    private $handler;

    public function init()
    {
        $this->handler = new ValidationExceptionHandler;
    }

    public function test_it_handles_familiar_exceptions()
    {
        $validException = new ValidationException(['messages']);

        $this->assertFalse($this->handler->manages(new Exception));
        $this->assertTrue($this->handler->manages($validException));
    }

    public function test_it_creates_the_desired_output()
    {
        $this->markTestIncomplete();

        $exception = new ValidationException(['field' => ['Some error']]);

        $response = $this->handler->handle($exception);

        $this->assertEquals(422, $response->getStatus());
    }
}
