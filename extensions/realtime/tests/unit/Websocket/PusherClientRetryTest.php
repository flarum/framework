<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\unit\Websocket;

use Flarum\Realtime\Websocket\PusherClientFactory;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The HTTP client the Pusher SDK uses to reach our own websocket server.
 *
 * Every push goes over this connection, and the server is restarted on every
 * deployment — which is also exactly when `BroadcastAssetsRevisionJob` fires, to
 * tell browsers the assets changed. A push caught mid-restart died with
 * `cURL error 56: Recv failure: Connection reset by peer` and, on a queue
 * configured `tries: 1`, failed permanently.
 *
 * The SDK builds its own client when none is given — `new Client(['timeout' => n])`
 * and nothing else, so no retry. It also only guards against `ConnectException`,
 * which a mid-response reset is not: `RequestException` and `ConnectException`
 * are siblings under `TransferException`, not parent and child. So a reset
 * escaped the SDK's own handling entirely.
 */
class PusherClientRetryTest extends TestCase
{
    private function request(): Request
    {
        return new Request('POST', 'http://localhost:8443/apps/1/events');
    }

    /**
     * @param mixed[] $queue responses and/or exceptions the transport will yield
     * @return array{0: \GuzzleHttp\Client, 1: MockHandler}
     */
    private function client(array $queue): array
    {
        $mock = new MockHandler($queue);

        // No sleeping in tests: the factory's delay is exercised separately.
        return [PusherClientFactory::make(3, $mock, fn () => 0), $mock];
    }

    /**
     * The reported failure: a connection reset partway through the response.
     */
    #[Test]
    public function it_retries_a_connection_reset_and_succeeds(): void
    {
        [$client] = $this->client([
            new RequestException('cURL error 56: Recv failure: Connection reset by peer', $this->request()),
            new Response(200, [], '{}'),
        ]);

        $response = $client->post('http://localhost:8443/apps/1/events');

        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * The websocket server refusing the connection outright — the same restart
     * window, caught a moment earlier. `ConnectException` does not extend
     * `RequestException`, so a decider written for one misses the other.
     */
    #[Test]
    public function it_retries_a_refused_connection_and_succeeds(): void
    {
        [$client] = $this->client([
            new ConnectException('cURL error 7: Failed to connect to localhost port 8443', $this->request()),
            new Response(200, [], '{}'),
        ]);

        $response = $client->post('http://localhost:8443/apps/1/events');

        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * Retrying is bounded. A server that stays down must surface, not spin —
     * the caller decides whether an undeliverable push is worth failing over.
     */
    #[Test]
    public function it_gives_up_once_the_attempts_are_exhausted(): void
    {
        [$client, $mock] = $this->client([
            new ConnectException('cURL error 7', $this->request()),
            new ConnectException('cURL error 7', $this->request()),
            new ConnectException('cURL error 7', $this->request()),
            new ConnectException('cURL error 7', $this->request()),
        ]);

        $this->expectException(ConnectException::class);

        try {
            $client->post('http://localhost:8443/apps/1/events');
        } finally {
            // One original attempt plus the retries, and no more.
            $this->assertLessThanOrEqual(3, 4 - $mock->count(), 'Retried more times than intended.');
        }
    }

    /**
     * A 4xx is the server answering, not failing to answer. Retrying it would
     * repeat a request that will be rejected identically — an auth signature
     * that does not verify, say.
     */
    #[Test]
    public function it_does_not_retry_a_rejected_request(): void
    {
        [$client, $mock] = $this->client([
            new Response(401, [], 'invalid signature'),
            new Response(200, [], '{}'),
        ]);

        $response = $client->post('http://localhost:8443/apps/1/events', ['http_errors' => false]);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(1, $mock->count(), 'A rejected request must not be retried.');
    }

    /**
     * A successful push costs no extra attempts.
     */
    #[Test]
    public function it_does_not_retry_a_successful_request(): void
    {
        [$client, $mock] = $this->client([
            new Response(200, [], '{}'),
            new Response(200, [], '{}'),
        ]);

        $client->post('http://localhost:8443/apps/1/events');

        $this->assertSame(1, $mock->count(), 'A successful request must not be retried.');
    }
}
