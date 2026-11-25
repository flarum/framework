<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\integration;

use Flarum\Realtime\Push\Audience;
use Flarum\Realtime\Push\Jobs\SendUserPayloadsJob;
use Flarum\Realtime\Push\RealtimeRegistry;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Pusher\Pusher;
use RuntimeException;

/**
 * A payload addressed to particular users reaches each of them on their own
 * channel, and only them; users given the same payload share a trigger. With
 * several to tell, only those connected are sent anything: one request to ask
 * rather than one per user who isn't there to hear it.
 */
class UserBroadcastRealtimeTest extends TestCase
{
    /** @var array<int, array{channels: string|string[], event: string, data: mixed}> */
    private array $triggered = [];

    /** @var int Requests asking who is connected. */
    private int $asked = 0;

    /**
     * A job for an event whose registration gives these payloads, worked out
     * when the job runs, and the registry it finds them in.
     *
     * @param array<int, array<string, mixed>> $payloads
     * @return array{0: SendUserPayloadsJob, 1: RealtimeRegistry}
     */
    private function job(array $payloads): array
    {
        $registry = new RealtimeRegistry();
        $registry->addUserBroadcast(['UserRead'], fn (object $event) => $event->payloads);

        return [new SendUserPayloadsJob('UserRead', (object) ['payloads' => $payloads], 0), $registry];
    }

    private function deliver(array $payloads, Pusher $pusher, Audience $audience): void
    {
        [$job, $registry] = $this->job($payloads);

        $job->handle($pusher, $audience, $registry);
    }

    /**
     * @param int[]|null $connected Null when the websocket server can't say.
     */
    private function audience(?array $connected): Audience
    {
        $this->app();

        $asked = &$this->asked;

        return new class($this->pusher(), $connected, $asked) extends Audience {
            public function __construct(Pusher $pusher, private ?array $connected, private int &$asked)
            {
                parent::__construct($pusher);
            }

            public function connectedUserIds(): ?array
            {
                $this->asked++;

                return $this->connected;
            }
        };
    }

    private function pusher(bool|string $failing = false): Pusher
    {
        $this->app();

        $triggered = &$this->triggered;

        return new class($triggered, $failing) extends Pusher {
            public function __construct(private array &$triggered, private bool|string $failing)
            {
                parent::__construct('key', 'secret', 'app');
            }

            public function trigger($channels, string $event, $data, array $params = [], bool $already_encoded = false): object
            {
                // True fails every trigger; a channel name fails only that one.
                if ($this->failing === true || in_array($this->failing, (array) $channels, true)) {
                    throw new RuntimeException('The websocket server is down.');
                }

                $this->triggered[] = ['channels' => $channels, 'event' => $event, 'data' => $data];

                return (object) [];
            }
        };
    }

    #[Test]
    public function each_connected_user_gets_their_own_payload_on_their_own_channel(): void
    {
        // 5 isn't connected.
        $this->deliver([
            2 => ['dialogId' => '5', 'messageId' => 10],
            3 => ['dialogId' => '5', 'messageId' => 12],
            4 => ['dialogId' => '5', 'messageId' => 10],
            5 => ['dialogId' => '5', 'messageId' => 14],
        ], $this->pusher(), $this->audience([1, 2, 3, 4]));

        $this->assertSame(1, $this->asked);
        $this->assertCount(2, $this->triggered);
        $this->assertSame(['private-user=2', 'private-user=4'], $this->triggered[0]['channels']);
        $this->assertSame('UserRead', $this->triggered[0]['event']);
        $this->assertSame(['dialogId' => '5', 'messageId' => 10], $this->triggered[0]['data']);
        $this->assertSame(['private-user=3'], $this->triggered[1]['channels']);
        $this->assertSame(['dialogId' => '5', 'messageId' => 12], $this->triggered[1]['data']);
    }

    #[Test]
    public function one_user_is_told_without_asking_who_is_connected(): void
    {
        $this->deliver([2 => ['dialogId' => '5']], $this->pusher(), $this->audience([]));

        $this->assertSame(0, $this->asked);
        $this->assertSame(['private-user=2'], $this->triggered[0]['channels']);
    }

    #[Test]
    public function nothing_is_sent_to_several_when_the_websocket_server_cannot_say_who_is_connected(): void
    {
        $this->deliver([2 => ['a' => 1], 3 => ['a' => 2]], $this->pusher(), $this->audience(null));

        $this->assertSame([], $this->triggered);
    }

    #[Test]
    public function many_users_with_the_same_payload_are_told_in_batches(): void
    {
        $payloads = array_fill_keys(range(1, 150), ['dialogId' => '5']);

        $this->deliver($payloads, $this->pusher(), $this->audience(range(1, 150)));

        $this->assertCount(2, $this->triggered);
        $this->assertCount(100, $this->triggered[0]['channels']);
        $this->assertCount(50, $this->triggered[1]['channels']);
    }

    #[Test]
    public function a_websocket_server_that_cannot_be_reached_does_not_fail_the_job(): void
    {
        // Run inline on a forum without a queue worker, the job would
        // otherwise fail the request that raised the event.
        $this->deliver([2 => ['dialogId' => '5']], $this->pusher(failing: true), $this->audience([2]));

        $this->assertSame([], $this->triggered);
    }

    #[Test]
    public function one_payload_that_fails_does_not_keep_the_others_from_being_sent(): void
    {
        $this->deliver([2 => ['a' => 1], 3 => ['a' => 2]], $this->pusher(failing: 'private-user=2'), $this->audience([2, 3]));

        $this->assertSame([['private-user=3']], array_column($this->triggered, 'channels'));
    }

    #[Test]
    public function the_payloads_are_worked_out_again_when_the_job_runs(): void
    {
        // Queued while user 2 was to be told; by the time a worker runs it,
        // they no longer are.
        $event = (object) ['payloads' => [2 => ['a' => 1]]];
        $registry = new RealtimeRegistry();
        $registry->addUserBroadcast(['UserRead'], fn (object $event) => $event->payloads);
        $job = new SendUserPayloadsJob('UserRead', $event, 0);

        $event->payloads = [];
        $job->handle($this->pusher(), $this->audience([2]), $registry);

        $this->assertSame([], $this->triggered);
    }

    #[Test]
    public function a_registration_that_no_longer_matches_the_event_sends_nothing(): void
    {
        // Extensions toggled while the job waited: index 0 is now another event's.
        $registry = new RealtimeRegistry();
        $registry->addUserBroadcast(['SomethingElse'], fn () => [2 => ['a' => 1]]);

        (new SendUserPayloadsJob('UserRead', (object) [], 0))->handle($this->pusher(), $this->audience([2]), $registry);

        $this->assertSame([], $this->triggered);
    }
}
