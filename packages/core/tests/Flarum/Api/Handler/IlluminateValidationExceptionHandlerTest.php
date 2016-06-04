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
use Flarum\Api\Handler\IlluminateValidationExceptionHandler;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Validation\Factory;
use Symfony\Component\Translation\Translator;
use Tests\Test\TestCase;

class IlluminateValidationExceptionHandlerTest extends TestCase
{
    private $handler;

    public function init()
    {
        $this->handler = new IlluminateValidationExceptionHandler;
    }

    public function test_it_handles_familiar_exceptions()
    {
        $validException = new ValidationException($this->makeValidator());

        $this->assertFalse($this->handler->manages(new Exception));
        $this->assertTrue($this->handler->manages($validException));
    }

    public function test_it_creates_the_desired_output()
    {
        $exception = new ValidationException($this->makeValidator(['foo' => ''], ['foo' => 'required']));

        $response = $this->handler->handle($exception);

        $this->assertEquals(422, $response->getStatus());
        $this->assertEquals([
            [
                'status' => '422',
                'code' => 'validation_error',
                'detail' => 'validation.required',
                'source' => ['pointer' => '/data/attributes/foo']
            ]
        ], $response->getErrors());
    }

    private function makeValidator($data = [], $rules = [])
    {
        $translator = new Translator('en');
        $factory = new Factory($translator);

        return $factory->make($data, $rules);
    }
}
