<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Api;

use Flarum\Api\ClientMiddlewarePipe;
use Flarum\Testing\unit\TestCase;
use Illuminate\Container\Container;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ClientMiddlewarePipeTest extends TestCase
{
    private function pipe(): ClientMiddlewarePipe
    {
        $container = new Container();

        $container->bind('flarum.api.error_handler', fn () => new class implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                try {
                    return $handler->handle($request);
                } catch (\Throwable) {
                    return new EmptyResponse(500);
                }
            }
        });

        $container->bind('route', fn () => new class implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                throw new \RuntimeException('boom');
            }
        });

        return new ClientMiddlewarePipe($container, ['route']);
    }

    #[Test]
    public function error_handling_can_be_enabled_after_the_pipe_was_built()
    {
        $pipe = $this->pipe();

        try {
            $pipe->errorHandling(false)->handle(new ServerRequest());
            $this->fail('The exception should not have been handled.');
        } catch (\RuntimeException) {
            // Expected, this builds the underlying pipe.
        }

        $response = $pipe->errorHandling(true)->handle(new ServerRequest());

        $this->assertEquals(500, $response->getStatusCode());
    }

    #[Test]
    public function error_handling_can_be_disabled_after_the_pipe_was_built()
    {
        $pipe = $this->pipe();

        $this->assertEquals(500, $pipe->errorHandling(true)->handle(new ServerRequest())->getStatusCode());

        $this->expectException(\RuntimeException::class);

        $pipe->errorHandling(false)->handle(new ServerRequest());
    }
}
