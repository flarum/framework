<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\integration;

use Flarum\Realtime\Push\Audience;
use Flarum\Realtime\Push\Jobs\SendDialogRemovalJob;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Pusher\Pusher;

/**
 * A dialog message deleted for good reaches only the members who could see it
 * and are connected, with nothing but which message it was.
 */
class DialogRemovalRealtimeTest extends TestCase
{
    /** @var array<int, array{channels: string|string[], event: string, data: mixed}> */
    private array $triggered = [];

    /**
     * @param int[]|null $connected
     */
    private function audience(?array $connected): Audience
    {
        $this->app();

        return new class($this->pusher(), $connected) extends Audience {
            public function __construct(Pusher $pusher, private ?array $connected)
            {
                parent::__construct($pusher);
            }

            public function connectedUserIds(): ?array
            {
                return $this->connected;
            }
        };
    }

    private function pusher(): Pusher
    {
        $triggered = &$this->triggered;

        return new class($triggered) extends Pusher {
            public function __construct(private array &$triggered)
            {
                parent::__construct('key', 'secret', 'app');
            }

            public function trigger($channels, string $event, $data, array $params = [], bool $already_encoded = false): object
            {
                $this->triggered[] = ['channels' => $channels, 'event' => $event, 'data' => $data];

                return (object) [];
            }
        };
    }

    #[Test]
    public function only_connected_members_who_could_see_it_are_told(): void
    {
        $job = new SendDialogRemovalJob('MessageDeleted', 10, 5, false, [2, 3, 4]);

        // 1 is connected but wasn't in the conversation; 4 was, but isn't connected.
        $job->handle($this->pusher(), $this->audience([1, 2, 3]));

        $this->assertCount(1, $this->triggered);
        $this->assertSame(['private-user=2', 'private-user=3'], $this->triggered[0]['channels']);
        $this->assertSame('MessageDeleted', $this->triggered[0]['event']);
        $this->assertSame([
            'data' => ['type' => 'dialog-messages', 'id' => '10'],
            'meta' => ['dialogId' => '5', 'dialogDeleted' => false],
        ], $this->triggered[0]['data']);
    }

    #[Test]
    public function nothing_is_sent_when_the_websocket_server_cannot_say_who_is_connected(): void
    {
        (new SendDialogRemovalJob('MessageDeleted', 10, 5, true, [2]))->handle($this->pusher(), $this->audience(null));

        $this->assertSame([], $this->triggered);
    }
}
