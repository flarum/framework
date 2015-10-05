<?php
namespace tests\Flarum\Admin\Middleware;

use Flarum\Admin\Middleware\LoginWithCookieAndCheckAdmin;
use Flarum\Core\Exceptions\PermissionDeniedException;
use Illuminate\Contracts\Container\Container;
use Mockery as m;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use tests\Test\TestCase;

class LoginWithCookieAndCheckAdminTest extends TestCase
{
    public function test_it_should_not_allow_invalid_logins()
    {
        $this->setExpectedException(PermissionDeniedException::class);

        $container = m::mock(Container::class);
        $request = m::mock(ServerRequestInterface::class);
        $response = m::mock(ResponseInterface::class);

        $request->shouldReceive('getCookieParams')->andReturn([]);

        $middleware = new LoginWithCookieAndCheckAdmin($container);
        $middleware->__invoke($request, $response);
    }
}
