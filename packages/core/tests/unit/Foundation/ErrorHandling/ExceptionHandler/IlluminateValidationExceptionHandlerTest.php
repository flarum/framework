<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Foundation\ErrorHandling\ExceptionHandler;

use Flarum\Foundation\ErrorHandling\ExceptionHandler\IlluminateValidationExceptionHandler;
use Flarum\Testing\unit\TestCase;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;

class IlluminateValidationExceptionHandlerTest extends TestCase
{
    private $handler;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->handler = new IlluminateValidationExceptionHandler;
    }

    public function test_it_creates_the_desired_output()
    {
        $exception = new ValidationException($this->makeValidator(['foo' => ''], ['foo' => 'required']));

        $error = $this->handler->handle($exception);

        $this->assertEquals(422, $error->getStatusCode());
        $this->assertEquals('validation_error', $error->getType());
        $this->assertEquals([
            [
                'detail' => 'validation.required',
                'source' => ['pointer' => '/data/attributes/foo']
            ]
        ], $error->getDetails());
    }

    private function makeValidator($data = [], $rules = [])
    {
        $translator = new Translator(new ArrayLoader(), 'en');
        $factory = new Factory($translator);

        return $factory->make($data, $rules);
    }
}
