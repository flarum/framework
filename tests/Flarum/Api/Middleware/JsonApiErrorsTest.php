<?php
namespace tests\Flarum\Api\Middleware;

use Flarum\Api\Middleware\JsonApiErrors;
use tests\Test\Stubs\ApiException;
use tests\Test\TestCase;

class JsonApiErrorsTest extends TestCase
{
    private $middleware;

    public function init()
    {
        $this->middleware = new JsonApiErrors;
    }

    public function test_it_should_handle_serializable_exceptions()
    {
        $response = $this->middleware->handle(new ApiException);

        $this->assertEquals(599, $response->getStatusCode());
        $this->assertEquals('{"errors":["error1","error2"]}', (string) $response->getBody());
    }
}
