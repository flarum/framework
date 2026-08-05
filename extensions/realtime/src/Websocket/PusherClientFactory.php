<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * The HTTP client the Pusher SDK uses to reach our own websocket server.
 *
 * Left to itself the SDK builds `new Client(['timeout' => n])` and nothing more,
 * so a push that fails in transit fails outright. That matters here because the
 * server on the other end is ours and is restarted on every deployment — which
 * is also when `BroadcastAssetsRevisionJob` runs, to tell browsers the assets
 * changed. A push caught in that window died with `cURL error 56: Recv failure:
 * Connection reset by peer`, and the realtime queue runs `tries: 1`, so it
 * failed permanently.
 *
 * The SDK's own guard does not help: it catches `ConnectException`, and a
 * mid-response reset is a `RequestException` — siblings under
 * `TransferException`, not parent and child.
 *
 * So the transport retries briefly. A websocket process comes back in far less
 * time than the delays below, and these payloads are small enough that repeating
 * one costs nothing worth counting.
 */
class PusherClientFactory
{
    /**
     * Attempts after the first. Two is enough to cross a process restart without
     * holding a queue worker while a server that is genuinely down stays down.
     */
    public const RETRIES = 2;

    /**
     * Build the client.
     *
     * @param int $timeout seconds, from the extension's own settings
     * @param callable|null $handler transport to use instead of the default, for tests
     * @param callable|null $delay milliseconds to wait before attempt $n, for tests
     */
    public static function make(int $timeout, ?callable $handler = null, ?callable $delay = null): Client
    {
        $stack = $handler ? HandlerStack::create($handler) : HandlerStack::create();

        $stack->push(Middleware::retry(
            self::decider(),
            $delay ?? self::delay()
        ));

        return new Client([
            'timeout' => $timeout,
            'handler' => $stack,
        ]);
    }

    /**
     * Retry only where the request never got an answer. A response — including a
     * rejection such as an auth signature that does not verify — is the server
     * answering, and repeating it would be told the same thing again.
     */
    private static function decider(): callable
    {
        return function (
            int $retries,
            RequestInterface $request,
            ?ResponseInterface $response = null,
            ?\Throwable $exception = null
        ): bool {
            if ($retries >= self::RETRIES) {
                return false;
            }

            return $response === null && $exception instanceof TransferException;
        };
    }

    /**
     * Short, growing waits: long enough for a restarting process to bind its
     * socket again, short enough not to stall the queue.
     */
    private static function delay(): callable
    {
        return fn (int $retries): int => 100 * $retries;
    }
}
