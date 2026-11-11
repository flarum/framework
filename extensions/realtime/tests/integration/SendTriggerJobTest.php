<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\integration;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Realtime\Push\Jobs\SendGeneratedPayloadJob;
use Flarum\Realtime\Push\Jobs\SendTriggerJob;
use Flarum\Testing\integration\TestCase;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\NullQueue;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;

class SendTriggerJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-realtime');

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => 'Public', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'Hidden', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 2, 'comment_count' => 1, 'hidden_at' => Carbon::now()],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Hello</p></t>'],
                ['id' => 2, 'number' => 1, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Hello</p></t>'],
            ],
        ]);
    }

    /**
     * A SendTriggerJob with no connected users, that records the discussion it filters them by,
     * so the test does not depend on a live Pusher `getChannels` call.
     */
    private function job(AbstractModel $model, ?Discussion &$filteredBy): SendTriggerJob
    {
        return new class('newPost', $model, $filteredBy) extends SendTriggerJob {
            public function __construct(string $event, AbstractModel $model, private ?Discussion &$filteredBy)
            {
                parent::__construct($event, $model);
            }

            protected function connectedUsers(?Discussion $visible = null): Collection
            {
                $this->filteredBy = $visible;

                return Collection::make();
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
    public function a_post_in_a_public_discussion_is_pushed_on_the_public_channel(): void
    {
        $this->app();

        $pushed = [];
        $filteredBy = null;

        ($this->job(Post::query()->findOrFail(1), $filteredBy))($this->recordingQueue($pushed));

        $this->assertCount(1, $pushed);
        $this->assertInstanceOf(SendGeneratedPayloadJob::class, $pushed[0]);
        $this->assertNull((new \ReflectionProperty($pushed[0], 'recipient'))->getValue($pushed[0]), 'The payload goes to the public channel');
    }

    #[Test]
    public function connected_users_are_filtered_by_the_posts_discussion(): void
    {
        $this->app();

        $pushed = [];
        $filteredBy = null;

        ($this->job(Post::query()->findOrFail(2), $filteredBy))($this->recordingQueue($pushed));

        $this->assertInstanceOf(Discussion::class, $filteredBy);
        $this->assertEquals(2, $filteredBy->id);
    }

    #[Test]
    public function a_post_in_a_discussion_guests_cannot_see_is_not_pushed_publicly(): void
    {
        $this->app();

        $pushed = [];
        $filteredBy = null;

        ($this->job(Post::query()->findOrFail(2), $filteredBy))($this->recordingQueue($pushed));

        $this->assertCount(0, $pushed);
    }
}
