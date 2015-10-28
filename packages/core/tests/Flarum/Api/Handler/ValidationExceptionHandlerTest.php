<?php
namespace Tests\Flarum\Api\Handler;

use Flarum\Api\Handler\ValidationExceptionHandler;
use Flarum\Core\Exception\ValidationException;
use Tests\Test\TestCase;

class ValidationExceptionHandlerTest extends TestCase
{
    private $handler;

    public function init()
    {
        $this->handler = new ValidationExceptionHandler;
    }

    public function test_it_handles_recognisable_exceptions()
    {
        $this->assertFalse($this->handler->manages(new \Exception));
        $this->assertTrue($this->handler->manages(new ValidationException([])));
    }

    public function test_managing_exceptions()
    {
        $response = $this->handler->handle(new ValidationException(['There was an error']));

        $this->assertEquals(422, $response->getStatus());
        $this->assertEquals([['source' => ['pointer' => '/data/attributes/0'], 'detail' => 'There was an error']], $response->getErrors());
    }
}
