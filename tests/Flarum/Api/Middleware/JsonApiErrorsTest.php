<?php
namespace tests\Flarum\Api\Middleware;

use Exception;
use Flarum\Api\Middleware\JsonApiErrors;
use Flarum\Support\Json\ErrorHandler;
use Flarum\Support\Json\FallbackExceptionHandler;
use Flarum\Support\Json\ModelNotFoundExceptionHandler;
use Flarum\Support\Json\SerializableErrorHandler;
use Flarum\Support\Json\ValidationExceptionHandler;
use Illuminate\Contracts\Support\MessageProvider;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery as m;
use tests\Test\Stubs\ApiException;
use tests\Test\TestCase;

class JsonApiErrorsTest extends TestCase
{
    private $middleware;

    public function init()
    {
        $errorHandler = new ErrorHandler;
        $errorHandler->registerHandler(new SerializableErrorHandler);
        $errorHandler->registerHandler(new ValidationExceptionHandler);
        $errorHandler->registerHandler(new ModelNotFoundExceptionHandler);
        $errorHandler->registerHandler(new FallbackExceptionHandler);

        $this->middleware = new JsonApiErrors($errorHandler);
    }

    public function test_it_should_handle_serializable_exceptions()
    {
        $response = $this->middleware->handle(new ApiException);

        $this->assertEquals(599, $response->getStatusCode());
        $this->assertEquals('{"errors":["error1","error2"]}', (string) $response->getBody());
    }
    
    public function test_it_should_handle_validation_exceptions()
    {
        $messageProvider = m::mock(MessageProvider::class);
        $messageProvider->shouldReceive('getMessageBag')->once()->andReturn(collect(['field' => ['message1', 'message2']]));

        $exception = new ValidationException($messageProvider);

        $response = $this->middleware->handle($exception);

        $this->assertEquals(422, $response->getStatusCode());
    }

    public function it_should_handle_model_not_found_exceptions()
    {
        $response = $this->middleware->handle(new ModelNotFoundException);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function it_should_handle_unknown_exceptions()
    {
        $response = $this->middleware->handle(new Exception);

        $this->assertEquals(500, $response->getStatusCode());
    }
}
