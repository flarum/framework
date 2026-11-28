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
use GuzzleHttp\Psr7\Uri;
use Illuminate\Container\Container;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Pusher\Pusher;
use Ratchet\ConnectionInterface;

class TriggerExcludesSocketTest extends TestCase
{
    private Manager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $settings = $this->createStub(Settings::class);
        $settings->method('__get')->willReturnCallback(fn (string $name) => match ($name) {
            'maxConnections' => 1000,
            'maxChannelsPerConnection' => 100,
            default => null,
        });

        $config = $this->createStub(Config::class);
        $config->method('url')->willReturn(new Uri('https://flarum.local'));

        $container = new Container();
        $container->instance(Settings::class, $settings);
        $container->instance(Config::class, $config);
        Container::setInstance($container);

        $this->manager = new Manager($settings, $config);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        parent::tearDown();
    }

    /**
     * @return array<string, int> how many messages each socket received
     */
    private function trigger(array $body): array
    {
        $channel = $this->manager->findOrCreate('private-user=2');
        $tabs = ['1.1' => $this->connection('1.1'), '2.2' => $this->connection('2.2')];

        foreach ($tabs as $tab) {
            $channel->saveConnection($tab);
        }

        $controller = new TriggerEvent($this->manager, new Pusher('key', 'secret', '1'));
        $controller((new ServerRequest())->withParsedBody(['name' => 'ownDiscussionRead', 'channels' => ['private-user=2'], 'data' => '{}'] + $body));

        return array_map(fn ($tab) => count($tab->sent), $tabs);
    }

    #[Test]
    public function the_socket_named_in_the_body_is_left_out(): void
    {
        $this->assertSame(['1.1' => 0, '2.2' => 1], $this->trigger(['socket_id' => '1.1']));
    }

    #[Test]
    public function without_one_every_socket_is_sent_it(): void
    {
        $this->assertSame(['1.1' => 1, '2.2' => 1], $this->trigger([]));
    }

    #[Test]
    public function a_malformed_socket_id_leaves_nobody_out(): void
    {
        $this->assertSame(['1.1' => 1, '2.2' => 1], $this->trigger(['socket_id' => ['1.1']]));
    }

    private function connection(string $socketId): ConnectionInterface
    {
        $connection = new class implements ConnectionInterface {
            public ?string $socketId = null;

            /** @var string[] */
            public array $sent = [];

            public function send($data): void
            {
                $this->sent[] = $data;
            }

            public function close(): void
            {
            }
        };
        $connection->socketId = $socketId;

        return $connection;
    }
}
