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
use Flarum\Post\Event\Posted;
use Flarum\Post\Post;
use Flarum\Realtime\Push\Jobs\SendGeneratedPayloadJob;
use Flarum\Realtime\Push\Jobs\SendTriggerJob;
use Flarum\Realtime\Push\Payload\Generator;
use Flarum\Tags\Tag;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Queue\NullQueue;
use PHPUnit\Framework\Attributes\Test;
use Pusher\Pusher;

/**
 * A post reaches the public channel when guests can see its discussion, and
 * connected members only when they can see it themselves.
 */
class SendTriggerJobTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-realtime');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(), // id 2
                ['id' => 3, 'username' => 'other', 'email' => 'other@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Public', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 2],
                // Hidden: only admin can see it.
                ['id' => 2, 'title' => 'Hidden', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 3, 'comment_count' => 2, 'hidden_at' => Carbon::now()],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>First</p></t>'],
                ['id' => 2, 'number' => 2, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Reply</p></t>'],
                ['id' => 3, 'number' => 1, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>First</p></t>'],
                ['id' => 4, 'number' => 2, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Reply</p></t>'],
            ],
        ]);
    }

    #[Test]
    public function a_reply_in_a_public_discussion_is_pushed_publicly_and_to_connected_members(): void
    {
        $recipients = $this->trigger(postId: 2, connected: [1, 2, 3], actorId: 1);

        // The actor is never sent their own reply.
        $this->assertSame(['2', '3', 'public'], $recipients);
    }

    #[Test]
    public function a_reply_in_a_hidden_discussion_only_goes_to_members_who_can_see_it(): void
    {
        $recipients = $this->trigger(postId: 4, connected: [1, 2, 3], actorId: null);

        $this->assertSame(['1'], $recipients);
    }

    /**
     * A reply awaiting approval in a public discussion: the public job is
     * queued, because guests can see the discussion, but what is actually
     * sent is decided per recipient, and a guest cannot see the post. Only
     * the admin, who can approve it, receives it.
     */
    #[Test]
    public function a_reply_awaiting_approval_reaches_nobody_who_cannot_approve_it(): void
    {
        $this->extension('flarum-realtime', 'flarum-approval');

        // As approval saves a held reply: unapproved, and private until it is
        // approved.
        $this->prepareDatabase([
            Post::class => [
                ['id' => 5, 'number' => 3, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>Pending</p></t>', 'is_approved' => 0, 'is_private' => 1],
            ],
        ]);

        // Posted by member 3, who never receives their own reply; member 2 and
        // guests cannot see it.
        $this->assertSame(['private-user=1'], $this->deliver(postId: 5, connected: [1, 2, 3], actorId: 3));
    }

    /**
     * A reply in a discussion under a restricted tag: guests cannot see the
     * discussion, so no public job is queued at all, and connected members
     * only receive it if they can see it.
     */
    #[Test]
    public function a_reply_in_a_restricted_tag_reaches_nobody_who_cannot_see_the_tag(): void
    {
        $this->extension('flarum-realtime', 'flarum-tags');

        $this->prepareDatabase([
            Tag::class => [
                ['id' => 1, 'name' => 'Staff', 'slug' => 'staff', 'position' => 0, 'is_restricted' => 1],
            ],
            Discussion::class => [
                ['id' => 3, 'title' => 'Staff only', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 6, 'comment_count' => 2],
            ],
            'discussion_tag' => [
                ['discussion_id' => 3, 'tag_id' => 1],
            ],
            Post::class => [
                ['id' => 6, 'number' => 1, 'discussion_id' => 3, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>First</p></t>'],
                ['id' => 7, 'number' => 2, 'discussion_id' => 3, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Reply</p></t>'],
            ],
        ]);

        $this->assertSame(['1'], $this->trigger(postId: 7, connected: [1, 2, 3], actorId: null));
        $this->assertSame(['private-user=1'], $this->deliver(postId: 7, connected: [1, 2, 3], actorId: null));
    }

    /**
     * Run the job for a post, then run every payload job it queued, and
     * return the channels something was actually sent on.
     *
     * @param int[] $connected
     * @return string[]
     */
    private function deliver(int $postId, array $connected, ?int $actorId): array
    {
        $sent = [];
        $pusher = $this->pusher($connected, $sent);
        $this->app()->getContainer()->instance(Pusher::class, $pusher);

        $post = Post::query()->findOrFail($postId);
        $actor = $actorId ? User::query()->findOrFail($actorId) : null;

        $pushed = [];
        (new SendTriggerJob(Posted::class, $post, $actor))($this->recordingQueue($pushed));

        $generator = $this->app()->getContainer()->make(Generator::class);

        foreach ($pushed as $job) {
            $job->handle($generator, $pusher);
        }

        sort($sent);

        return $sent;
    }

    /**
     * Run the job for a post and return who each queued payload is for: a
     * user id, or `public`.
     *
     * @param int[] $connected
     * @return string[]
     */
    private function trigger(int $postId, array $connected, ?int $actorId): array
    {
        $this->app()->getContainer()->instance(Pusher::class, $this->pusher($connected));

        $post = Post::query()->findOrFail($postId);
        $actor = $actorId ? User::query()->findOrFail($actorId) : null;

        $pushed = [];
        (new SendTriggerJob(Posted::class, $post, $actor))($this->recordingQueue($pushed));

        $recipients = [];

        foreach ($pushed as $job) {
            $this->assertInstanceOf(SendGeneratedPayloadJob::class, $job);

            /** @var User|null $recipient */
            $recipient = (new \ReflectionProperty($job, 'recipient'))->getValue($job);
            $recipients[] = $recipient ? (string) $recipient->id : 'public';
        }

        sort($recipients);

        return $recipients;
    }

    /**
     * @param int[] $connected
     * @param string[] $sent channels triggered, filled in as it happens
     */
    private function pusher(array $connected, array &$sent = []): Pusher
    {
        return new class($connected, $sent) extends Pusher {
            /**
             * @param int[] $connected
             * @param string[] $sent
             */
            public function __construct(private array $connected, private array &$sent)
            {
                parent::__construct('key', 'secret', 'app');
            }

            public function trigger($channels, string $event, $data, array $params = [], bool $already_encoded = false): object
            {
                foreach ((array) $channels as $channel) {
                    $this->sent[] = $channel;
                }

                return (object) [];
            }

            public function getChannels(array $params = []): object
            {
                $channels = [];

                foreach ($this->connected as $id) {
                    $channels["private-user=$id"] = (object) [];
                }

                return (object) ['channels' => $channels];
            }
        };
    }

    /**
     * @param array<int, object> $pushed
     */
    private function recordingQueue(array &$pushed): NullQueue
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
}
