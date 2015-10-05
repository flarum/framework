<?php
namespace tests\Flarum\Support\Json;

use Exception;
use Flarum\Support\Json\ErrorHandler;
use Flarum\Support\Json\InvalidHandlerException;
use tests\Test\TestCase;

class ErrorHandlerTest extends TestCase
{
    private $handler;

    public function init()
    {
        $this->handler = new ErrorHandler;
    }
    
    public function test_it_should_throw_an_exception_when_no_handlers_are_present()
    {
        $this->setExpectedException(InvalidHandlerException::class);

        $this->handler->handle(new Exception);
    }
}
