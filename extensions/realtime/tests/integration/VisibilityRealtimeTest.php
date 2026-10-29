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
use Flarum\Discussion\Event\Hidden as DiscussionHidden;
use Flarum\Post\Post;
use Flarum\Realtime\Push\Audience;
use Flarum\Realtime\Push\Jobs\SendRemovalJob;
use Flarum\Realtime\Push\Jobs\SendTriggerJob;
use Flarum\Realtime\Push\VisibilitySubscriber;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\NullQueue;
use PHPUnit\Framework\Attributes\Test;
use Pusher\Pusher;

/**
 * Hidden and deleted content is removed from screens live, but only for
 * members who could see it: nobody is told something existed that they could
 * never see. Members who can still see it (admin, here) get its new state
 * instead of a removal.
 */
class VisibilityRealtimeTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /** @var array<int, array{channels: string|string[], event: string, data: mixed}> */
    private array $triggered = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-realtime');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(), // id 2: an ordinary member
                ['id' => 3, 'username' => 'outsider', 'email' => 'outsider@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                // Authored by admin: authors can still see their own hidden content.
                ['id' => 1, 'title' => 'Public', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 2],
                // Hidden from guests and outsiders, so only members who can see hidden discussions (admin) see it.
                ['id' => 2, 'title' => 'Already hidden', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 3, 'comment_count' => 1, 'hidden_at' => Carbon::now()],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>First</p></t>'],
                // Hidden, and by the outsider, so member 2 can't see it but its author still can.
                ['id' => 2, 'number' => 2, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>Reply</p></t>', 'hidden_at' => Carbon::now()],
                ['id' => 3, 'number' => 1, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Secret</p></t>'],
            ],
        ]);
    }

    /**
     * @param int[] $connected
     */
    private function audience(array $connected): Audience
    {
        $this->app();

        return new class($this->pusher(), $connected) extends Audience {
            public function __construct(Pusher $pusher, private array $connected)
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

    /**
     * @return string[]
     */
    private function removalChannels(): array
    {
        $channels = [];

        foreach ($this->triggered as $trigger) {
            if ($trigger['event'] === SendRemovalJob::EVENT) {
                array_push($channels, ...(array) $trigger['channels']);
            }
        }

        sort($channels);

        return $channels;
    }

    #[Test]
    public function a_hidden_post_is_removed_for_those_who_can_no_longer_see_it(): void
    {
        // Post 2 is hidden: member 2 can't see it now; admin (moderator) and its author can.
        SendRemovalJob::forPost(2, 1)->handle($this->pusher(), $this->audience([1, 2, 3]));

        $this->assertSame(['private-user=2', 'public'], $this->removalChannels());
        $this->assertSame(['type' => 'posts', 'id' => '2'], $this->triggered[0]['data']['data']);
    }

    #[Test]
    public function a_deleted_post_is_removed_for_everyone_who_could_see_its_discussion(): void
    {
        $this->app();
        Post::query()->whereKey(1)->delete();

        SendRemovalJob::forPost(1, 1)->handle($this->pusher(), $this->audience([1, 2]));

        $this->assertSame(['private-user=1', 'private-user=2', 'public'], $this->removalChannels());
    }

    #[Test]
    public function a_removed_discussion_is_only_announced_to_those_who_could_see_it(): void
    {
        // Captured before deletion: only admin (user 1) could see discussion 2.
        $this->app();
        Discussion::query()->whereKey(2)->delete();

        SendRemovalJob::forDiscussion(2, ['public' => false, 'users' => [1]])->handle($this->pusher(), $this->audience([1, 2, 3]));

        $this->assertSame(['private-user=1'], $this->removalChannels());
    }

    #[Test]
    public function without_a_snapshot_nobody_is_told(): void
    {
        SendRemovalJob::forDiscussion(2, null)->handle($this->pusher(), $this->audience([1, 2, 3]));

        $this->assertSame([], $this->triggered);
    }

    #[Test]
    public function hiding_a_public_discussion_snapshots_its_audience_first(): void
    {
        $pushed = [];
        $this->app()->getContainer()->instance(Queue::class, $this->recordingQueue($pushed));

        $subscriber = new VisibilitySubscriber($this->audience([1, 2, 3]));
        $discussion = Discussion::query()->findOrFail(1);
        $admin = User::query()->findOrFail(1);

        $discussion->hide($admin);
        $subscriber->discussionSaving($discussion);
        $discussion->save();
        $subscriber->discussionHidden(new DiscussionHidden($discussion, $admin));

        $removal = array_values(array_filter($pushed, fn ($job) => $job instanceof SendRemovalJob))[0];
        $trigger = array_values(array_filter($pushed, fn ($job) => $job instanceof SendTriggerJob))[0];

        // Guests could see it beforehand, so everyone connected who can't now is told.
        $removal->handle($this->pusher(), $this->audience([1, 2, 3]));

        $this->assertSame(['private-user=2', 'private-user=3', 'public'], $this->removalChannels());
        $this->assertNotNull($trigger);
    }

    /**
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
}
