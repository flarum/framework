<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\unit\Websocket;

use Flarum\Foundation\Config;
use Flarum\Realtime\Websocket\Channel\Manager;
use Flarum\Realtime\Websocket\Connection\TriggerEvent;
use Flarum\Realtime\Websocket\Settings;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Container\Container;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Pusher\Pusher;
use Ratchet\ConnectionInterface;

/**
 * One controller serves every connection to its route, and a request's body can
 * arrive after its headers. A request that starts while another one's body is
 * still coming in must not take that request's place.
 */
class HttpControllerRequestStateTest extends TestCase
{
    private const APP_ID = '1';
    private const APP_KEY = 'test-app-key';
    private const APP_SECRET = 'test-app-secret';

    private TriggerEvent $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container();

        $settings = $this->createStub(Settings::class);
        $settings->method('__get')->willReturnCallback(fn (string $name) => match ($name) {
            'appKey' => self::APP_KEY,
            'appSecret' => self::APP_SECRET,
            'maxConnections' => 1000,
            'maxChannelsPerConnection' => 100,
            default => null,
        });

        $config = $this->createStub(Config::class);
        $config->method('url')->willReturn(new Uri('https://flarum.local'));

        $container->instance(Settings::class, $settings);
        Container::setInstance($container);

        $this->controller = new TriggerEvent(
            new Manager($settings, $config),
            new Pusher(self::APP_KEY, self::APP_SECRET, self::APP_ID)
        );
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        parent::tearDown();
    }

    #[Test]
    public function a_request_whose_body_arrives_in_parts_is_answered_once_it_is_complete(): void
    {
        [$request, $body] = $this->request('probe-a');
        $connection = $this->connection();

        $this->controller->onOpen($connection, $this->withBody($request, substr($body, 0, 20)));
        $this->assertSame([], $connection->responses);

        $this->controller->onMessage($connection, substr($body, 20));
        $this->assertSame([[200, 'probe-a']], $connection->responses);
    }

    #[Test]
    public function a_request_that_starts_while_another_is_still_arriving_does_not_take_its_place(): void
    {
        [$requestA, $bodyA] = $this->request('probe-a');
        [$requestB, $bodyB] = $this->request('probe-b');
        $a = $this->connection();
        $b = $this->connection();

        $this->controller->onOpen($a, $this->withBody($requestA, substr($bodyA, 0, 20)));
        $this->controller->onOpen($b, $this->withBody($requestB, $bodyB));
        $this->controller->onMessage($a, substr($bodyA, 20));

        $this->assertSame([[200, 'probe-b']], $b->responses);
        $this->assertSame([[200, 'probe-a']], $a->responses);
    }

    #[Test]
    public function a_connection_closed_before_its_body_arrives_leaves_the_next_request_unaffected(): void
    {
        [$requestA, $bodyA] = $this->request('probe-a');
        [$requestB, $bodyB] = $this->request('probe-b');
        $a = $this->connection();
        $b = $this->connection();

        $this->controller->onOpen($a, $this->withBody($requestA, substr($bodyA, 0, 20)));
        $this->controller->onClose($a);

        $this->controller->onOpen($b, $this->withBody($requestB, substr($bodyB, 0, 20)));
        $this->controller->onMessage($b, substr($bodyB, 20));

        $this->assertSame([], $a->responses);
        $this->assertSame([[200, 'probe-b']], $b->responses);
    }

    /**
     * A signed request to trigger an event on no channels, as the forum's Pusher
     * client sends it.
     *
     * @return array{0: Request, 1: string}
     */
    private function request(string $name): array
    {
        $body = json_encode(['name' => $name, 'channels' => [], 'data' => str_repeat('x', 100)]);
        $path = '/apps/'.self::APP_ID.'/events';

        $params = [
            'auth_key' => self::APP_KEY,
            'auth_timestamp' => time(),
            'auth_version' => '1.0',
            'body_md5' => md5($body),
        ];
        ksort($params);
        $params['auth_signature'] = hash_hmac('sha256', "POST\n$path\n".Pusher::array_implode('=', '&', $params), self::APP_SECRET);

        $request = new Request('POST', "http://localhost$path?".http_build_query($params), [
            'Content-Type' => 'application/json',
            'Content-Length' => (string) strlen($body),
        ]);

        return [$request, $body];
    }

    /**
     * The request as the HTTP server hands it over: the headers, and as much of
     * the body as has arrived with them.
     */
    private function withBody(Request $request, string $received): Request
    {
        return $request->withBody(\GuzzleHttp\Psr7\Utils::streamFor($received));
    }

    private function connection(): ConnectionInterface
    {
        return new class implements ConnectionInterface {
            /** @var array<int, array{0: int, 1: string|null}> */
            public array $responses = [];

            public function send($data): void
            {
                $response = Message::parseResponse($data);

                $this->responses[] = [$response->getStatusCode(), json_decode((string) $response->getBody(), true)['name'] ?? null];
            }

            public function close(): void
            {
            }
        };
    }
}
