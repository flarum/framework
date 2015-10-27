<?php
namespace tests\Flarum\Api\Handler;

use Flarum\Api\Handler\FloodingExceptionHandler;
use Flarum\Core\Exception\FloodingException;
use Tests\Test\TestCase;

class FloodingExceptionHandlerTest extends TestCase
{
    private $handler;

    public function init()
    {
        $this->handler = new FloodingExceptionHandler;
    }

    public function test_it_handles_recognisable_exceptions()
    {
        $this->assertFalse($this->handler->manages(new \Exception));
        $this->assertTrue($this->handler->manages(new FloodingException));
    }
}
