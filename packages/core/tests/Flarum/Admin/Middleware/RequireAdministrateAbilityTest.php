<?php
namespace Tests\Flarum\Admin\Middleware;

use Flarum\Admin\Middleware\AuthenticateWithCookie;
use Flarum\Admin\Middleware\RequireAdministrateAbility;
use Flarum\Core\Access\Gate;
use Flarum\Core\Exception\PermissionDeniedException;
use Mockery as m;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tests\Test\TestCase;

class RequireAdministrateAbilityTest extends TestCase
{
    public function test_it_should_not_allow_invalid_logins()
    {
        $this->setExpectedException(PermissionDeniedException::class);

        $gate = m::mock(Gate::class);
        $request = m::mock(ServerRequestInterface::class)->shouldIgnoreMissing();
        $response = m::mock(ResponseInterface::class);

        $gate->shouldReceive('forUser->allows')->andReturn(false);

        $middleware = new RequireAdministrateAbility($gate);
        $middleware->__invoke($request, $response);
    }
}
