<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Closure;
use Flarum\Extend;
use Flarum\Http\Middleware\IlluminateMiddlewareInterface;
use Flarum\Testing\integration\TestCase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MiddlewareTest extends TestCase
{
    // This adds the first custom middleware for test that require a middleware to already exist
    private function add_first_middleware()
    {
        $this->extend(
            (new Extend\Middleware('forum'))->add(FirstTestMiddleware::class)
        );
    }

    /**
     * @test
     */
    public function custom_header_is_not_present_by_default()
    {
        $response = $this->send($this->request('GET', '/'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($response->headers->get('X-First-Test-Middleware'));
    }

    /**
     * @test
     */
    public function can_add_middleware()
    {
        $this->extend(
            (new Extend\Middleware('forum'))->add(FirstTestMiddleware::class)
        );

        $response = $this->send($this->request('GET', '/'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull($response->headers->get('X-First-Test-Middleware'));
    }

    /**
     * @test
     */
    public function can_replace_middleware()
    {
        $this->add_first_middleware();
        $this->extend(
            (new Extend\Middleware('forum'))->replace(FirstTestMiddleware::class, SecondTestMiddleware::class)
        );

        $response = $this->send($this->request('GET', '/'));

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertNull($response->headers->get('X-First-Test-Middleware'));
        $this->assertNotNull($response->headers->get('X-Second-Test-Middleware'));
    }

    /**
     * @test
     */
    public function can_remove_middleware()
    {
        $this->add_first_middleware();
        $this->extend(
            (new Extend\Middleware('forum'))->remove(FirstTestMiddleware::class)
        );

        $response = $this->send($this->request('GET', '/'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($response->headers->get('X-First-Test-Middleware'));
    }

    /**
     * @test
     */
    public function can_insert_before_middleware()
    {
        $this->add_first_middleware();
        $this->extend(
            (new Extend\Middleware('forum'))->insertBefore(FirstTestMiddleware::class, SecondTestMiddleware::class)
        );

        $response = $this->send($this->request('GET', '/'));
        $headers = $response->headers->all();
        $newMiddlewarePosition = array_search(strtolower('X-Second-Test-Middleware'), array_keys($headers));
        $originalMiddlewarePosition = array_search(strtolower('X-First-Test-Middleware'), array_keys($headers));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertLessThan($newMiddlewarePosition, $originalMiddlewarePosition);
    }

    /**
     * @test
     */
    public function can_insert_after_middleware()
    {
        $this->add_first_middleware();
        $this->extend(
            (new Extend\Middleware('forum'))->insertAfter(FirstTestMiddleware::class, SecondTestMiddleware::class)
        );

        $response = $this->send($this->request('GET', '/'));
        $headers = $response->headers->all();
        $newMiddlewarePosition = array_search(strtolower('X-Second-Test-Middleware'), array_keys($headers));
        $originalMiddlewarePosition = array_search(strtolower('X-First-Test-Middleware'), array_keys($headers));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertGreaterThan($newMiddlewarePosition, $originalMiddlewarePosition);
    }
}

class FirstTestMiddleware implements IlluminateMiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-First-Test-Middleware', 'This is a test!');

        return $response;
    }
}

class SecondTestMiddleware implements IlluminateMiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Second-Test-Middleware', 'This is another test!');

        return $response;
    }
}
