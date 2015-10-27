<?php
namespace Tests\Flarum\Api\Handler;

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

    public function test_it_provides_expected_output()
    {
        $result = $this->handler->handle(new FloodingException);

        $this->assertEquals(429, $result->getStatus());
        $this->assertEquals([[]], $result->getErrors());
    }
}
