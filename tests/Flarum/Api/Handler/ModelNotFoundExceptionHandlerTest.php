<?php
namespace Tests\Flarum\Api\Handler;

use Flarum\Api\Handler\ModelNotFoundExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\Test\TestCase;

class ModelNotFoundExceptionHandlerTest extends TestCase
{
    private $handler;

    public function init()
    {
        $this->handler = new ModelNotFoundExceptionHandler;
    }

    public function test_it_handles_recognisable_exceptions()
    {
        $this->assertFalse($this->handler->manages(new \Exception));
        $this->assertTrue($this->handler->manages(new ModelNotFoundException));
    }

    public function test_managing_exceptions()
    {
        $response = $this->handler->handle(new ModelNotFoundException);

        $this->assertEquals(404, $response->getStatus());
        $this->assertEquals([[]], $response->getErrors());
    }
}
