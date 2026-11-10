<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\integration;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Mentions\Notification\UserMentionedBlueprint;
use Flarum\Notification\Event\Sent;
use Flarum\Notification\Notification;
use Flarum\Post\Post;
use Flarum\Realtime\Push\Jobs\SendGeneratedPayloadJob;
use Flarum\Realtime\Push\Jobs\SendNotificationsJob;
use Flarum\Realtime\Push\Listener\BroadcastNotifications;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\NullQueue;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;

class SendNotificationsJobTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-realtime', 'flarum-mentions');

        // user 2 (recipient) has TWO `userMentioned` notifications from different mentions:
        //   - one from `glowingblue` (user 4) on post 11 — this is the mention whose blueprint fires
        //   - one from `wlork` (user 3) on post 10 — an unrelated mention that is NEWER in the table
        //
        // The bug: the broadcast job selected "this user's latest notification of this type"
        // (`type` + `latest()`), which returns wlork's newer-but-unrelated notification instead of
        // the one the firing blueprint actually produced. This reproduces the reported case where a
        // mention from glowingblue surfaced a previous mention from wlork in the toast.
        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'wlork', 'email' => 'wlork@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'glowingblue', 'email' => 'glowingblue@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Hello world', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 10, 'comment_count' => 3],
            ],
            Post::class => [
                ['id' => 10, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>@you</p></t>'],
                ['id' => 11, 'number' => 2, 'discussion_id' => 1, 'created_at' => Carbon::now()->subMinute(), 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>@you</p></t>'],
            ],
            Notification::class => [
                // glowingblue's mention (post 11) — the one whose blueprint fires below. Older row.
                ['id' => 1, 'user_id' => 2, 'from_user_id' => 4, 'type' => 'userMentioned', 'subject_id' => 11, 'data' => null, 'created_at' => Carbon::now()->subMinute(), 'read_at' => null, 'is_deleted' => 0],
                // wlork's mention (post 10) — unrelated, but the NEWEST userMentioned row for this user.
                ['id' => 2, 'user_id' => 2, 'from_user_id' => 3, 'type' => 'userMentioned', 'subject_id' => 10, 'data' => null, 'created_at' => Carbon::now(), 'read_at' => null, 'is_deleted' => 0],
            ],
        ]);
    }

    /**
     * A SendNotificationsJob whose connected-user set is supplied directly, so the test does not
     * depend on a live Pusher `getChannels` call.
     *
     * @param User[] $recipients
     * @param User[] $connected
     */
    private function jobWithConnected(UserMentionedBlueprint $blueprint, array $recipients, array $connected): SendNotificationsJob
    {
        return new class($blueprint, $recipients, $connected) extends SendNotificationsJob {
            /**
             * @param User[] $recipients
             * @param User[] $connected
             */
            public function __construct(UserMentionedBlueprint $blueprint, array $recipients, private array $connected)
            {
                parent::__construct($blueprint, $recipients);
            }

            protected function connectedUsers(?Discussion $visible = null): Collection
            {
                return Collection::make($this->connected);
            }
        };
    }

    /**
     * A Queue stub that records every job pushed to it without running it.
     *
     * @param array<int, object> $pushed
     */
    private function recordingQueue(array &$pushed): Queue
    {
        return new class($pushed) extends NullQueue {
            /** @param array<int, object> $pushed */
            public function __construct(private array &$pushed)
            {
            }

            public function push($job, $data = '', $queue = null)
            {
                $this->pushed[] = $job;

                return null;
            }
        };
    }

    #[Test]
    public function it_broadcasts_the_notification_matching_the_fired_blueprint(): void
    {
        $this->app();

        /** @var Post $firedPost */
        $firedPost = Post::query()->findOrFail(11);
        /** @var User $recipient */
        $recipient = User::query()->findOrFail(2);

        // The blueprint that actually fired: glowingblue's mention on post 11.
        $blueprint = new UserMentionedBlueprint($firedPost);

        $pushed = [];
        $job = $this->jobWithConnected($blueprint, [$recipient], [$recipient]);

        $job->handle($this->recordingQueue($pushed));

        $this->assertCount(1, $pushed, 'Exactly one payload job should be pushed for the connected recipient');

        /** @var SendGeneratedPayloadJob $payloadJob */
        $payloadJob = $pushed[0];
        $this->assertInstanceOf(SendGeneratedPayloadJob::class, $payloadJob);

        $notification = $this->readPrivate($payloadJob, 'model');
        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals(1, $notification->id, 'The broadcast must use the notification from the mention that fired (glowingblue), not the most recent unrelated one (wlork)');
        $this->assertEquals(4, $notification->from_user_id, 'from_user must be glowingblue (user 4)');
        $this->assertEquals(11, $notification->subject_id, 'subject must be the post that fired (11)');
    }

    #[Test]
    public function it_skips_recipients_who_do_not_want_an_alert(): void
    {
        $this->app();

        /** @var Post $firedPost */
        $firedPost = Post::query()->findOrFail(11);
        /** @var User $recipient */
        $recipient = User::query()->findOrFail(2);

        // Opt the recipient out of the realtime alert for this type.
        $recipient->setPreference('notify_userMentioned_alert', false);
        $recipient->save();

        $blueprint = new UserMentionedBlueprint($firedPost);

        $pushed = [];
        $job = $this->jobWithConnected($blueprint, [$recipient], [$recipient]);

        $job->handle($this->recordingQueue($pushed));

        $this->assertCount(0, $pushed, 'No payload job should be pushed for a recipient who opted out of the alert');
    }

    #[Test]
    public function it_skips_recipients_who_are_not_connected(): void
    {
        $this->app();

        /** @var Post $firedPost */
        $firedPost = Post::query()->findOrFail(11);
        /** @var User $recipient */
        $recipient = User::query()->findOrFail(2);

        $blueprint = new UserMentionedBlueprint($firedPost);

        $pushed = [];
        // Recipient is targeted but not on the socket — nothing to broadcast.
        $job = $this->jobWithConnected($blueprint, [$recipient], []);

        $job->handle($this->recordingQueue($pushed));

        $this->assertCount(0, $pushed, 'No payload job should be pushed for a recipient who is not connected');
    }

    #[Test]
    public function listener_queues_the_broadcast_job_for_a_sent_event(): void
    {
        $this->app();

        /** @var Post $firedPost */
        $firedPost = Post::query()->findOrFail(11);
        /** @var User $recipient */
        $recipient = User::query()->findOrFail(2);

        $blueprint = new UserMentionedBlueprint($firedPost);

        $pushed = [];
        $listener = new BroadcastNotifications($this->recordingQueue($pushed));

        $listener->handle(new Sent($blueprint, [$recipient]));

        $this->assertCount(1, $pushed, 'The listener should queue exactly one broadcast job');
        $this->assertInstanceOf(SendNotificationsJob::class, $pushed[0]);
    }

    #[Test]
    public function listener_does_nothing_without_recipients(): void
    {
        $this->app();

        /** @var Post $firedPost */
        $firedPost = Post::query()->findOrFail(11);

        $blueprint = new UserMentionedBlueprint($firedPost);

        $pushed = [];
        $listener = new BroadcastNotifications($this->recordingQueue($pushed));

        $listener->handle(new Sent($blueprint, []));

        $this->assertCount(0, $pushed, 'The listener should not queue a broadcast job when there are no recipients');
    }

    private function readPrivate(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);

        return $reflection->getValue($object);
    }
}
