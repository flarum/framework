<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Akismet\Tests\fixtures;

use Flarum\Akismet\Akismet;
use Flarum\Foundation\AbstractServiceProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

/**
 * Rebinds the Akismet client onto a Guzzle mock handler so integration tests
 * can script Akismet's answers and inspect what was sent, without any real
 * HTTP. Registered from a test's extenders, it wins over the extension's own
 * binding because it registers later.
 *
 * Tests interact through the static state: queue responses (or exceptions)
 * before acting, read {@link $history} afterwards.
 */
class FakeAkismetProvider extends AbstractServiceProvider
{
    /** @var array<int, mixed> responses (Psr Response or Throwable) to serve, in order */
    public static array $queue = [];

    /** @var array<int, array{request: \Psr\Http\Message\RequestInterface, options: array}> */
    public static array $history = [];

    public static function reset(array $queue = []): void
    {
        static::$queue = $queue;
        static::$history = [];
    }

    public function register(): void
    {
        $this->container->bind(Akismet::class, function () {
            $mock = new MockHandler(static::$queue);
            $stack = HandlerStack::create($mock);
            $stack->push(Middleware::history(static::$history));

            return new Akismet(
                'fake-key',
                'https://forum.example.com',
                '2.0.0',
                'test',
                false,
                new Client(['handler' => $stack])
            );
        });
    }
}
