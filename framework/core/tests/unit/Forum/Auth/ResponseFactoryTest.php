<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Forum\Auth;

use Flarum\Forum\Auth\ResponseFactory;
use Flarum\Http\Rememberer;
use Flarum\Testing\unit\TestCase;
use Laminas\Diactoros\Response\RedirectResponse;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;

class ResponseFactoryTest extends TestCase
{
    private Rememberer $rememberer;
    private ResponseFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rememberer = m::mock(Rememberer::class);
        $this->factory = new ResponseFactory($this->rememberer);
    }

    #[Test]
    public function registration_response_redirects_with_flarum_auth_param(): void
    {
        // No LoginProvider match, no email match — new user path.
        // We test this via makeRegistrationResponse indirectly by verifying
        // the redirect URL shape when a new token is produced.
        //
        // We can't fully exercise make() without a database, so we use
        // integration tests for that. Here we test the redirect URL format.

        // Access private method via reflection to test URL construction in isolation.
        $method = new \ReflectionMethod(ResponseFactory::class, 'makeRegistrationResponse');

        $response = $method->invoke($this->factory, 'abc123token', '/d/42-discussion');

        $this->assertInstanceOf(RedirectResponse::class, $response);

        $location = $response->getHeaderLine('Location');
        $this->assertStringContainsString('_flarum_auth=', $location);
        $this->assertStringContainsString('abc123token', $location);
        $this->assertStringStartsWith('/d/42-discussion', $location);
    }

    #[Test]
    public function registration_response_uses_slash_when_returnTo_is_empty(): void
    {
        $method = new \ReflectionMethod(ResponseFactory::class, 'makeRegistrationResponse');

        $response = $method->invoke($this->factory, 'sometoken', '');

        $location = $response->getHeaderLine('Location');
        $this->assertStringStartsWith('/?_flarum_auth=', $location);
    }

    #[Test]
    public function registration_response_appends_param_correctly_when_returnTo_has_query(): void
    {
        $method = new \ReflectionMethod(ResponseFactory::class, 'makeRegistrationResponse');

        $response = $method->invoke($this->factory, 'sometoken', '/page?existing=1');

        $location = $response->getHeaderLine('Location');
        $this->assertStringContainsString('existing=1', $location);
        $this->assertStringContainsString('&_flarum_auth=', $location);
    }
}
