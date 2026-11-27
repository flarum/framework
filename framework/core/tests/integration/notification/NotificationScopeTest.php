<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\notification;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Started;
use Flarum\Extend;
use Flarum\Notification\AlertableInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Notification;
use Flarum\Notification\NotificationScope;
use Flarum\Notification\NotificationSyncer;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Revised;
use Flarum\Post\Post;
use Flarum\Queue\AbstractJob;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use PHPUnit\Framework\Attributes\Test;

class NotificationScopeTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extend(
            (new Extend\Notification())
                ->type(FollowedBlueprint::class, ['alert'])
                ->type(MentionedBlueprint::class, ['alert'])
        );

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Scoped', 'created_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'last_post_number' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>First</p></t>', 'number' => 1],
            ],
        ]);
    }

    private function notifyOn(string $followersEvent = Posted::class, string $mentionedEvent = Posted::class): void
    {
        $this->extend(
            (new Extend\Event())
                ->listen($followersEvent, NotifyFollowers::class)
                ->listen($mentionedEvent, NotifyMentioned::class)
        );
    }

    private function reply(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => ['content' => 'A reply'],
                        'relationships' => ['discussion' => ['data' => ['type' => 'discussions', 'id' => '1']]],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());
    }

    private function work(): void
    {
        $container = $this->app()->getContainer();
        $failed = [];
        $container->make(Dispatcher::class)->listen(JobFailed::class, function (JobFailed $event) use (&$failed) {
            $failed[] = $event->exception->getMessage();
        });

        for ($i = 0; $i < 20 && $this->database()->table('queue_jobs')->count(); $i++) {
            $container->make(Worker::class)->runNextJob('flarum', 'default', new WorkerOptions(sleep: 0, maxTries: 1));
        }

        $this->assertSame(0, $this->database()->table('queue_jobs')->count(), 'the worker ran every job');
        $this->assertSame([], $failed, 'no job failed');
    }

    private function notificationsOfUser2(): array
    {
        return Notification::query()->where('user_id', 2)->orderBy('id')->pluck('type')->all();
    }

    #[Test]
    public function one_reply_notifies_a_user_once_on_the_sync_queue()
    {
        $this->notifyOn();
        $this->reply();

        $this->assertCount(1, $this->notificationsOfUser2());
    }

    #[Test]
    public function one_reply_notifies_a_user_once_on_a_worker_queue()
    {
        $this->notifyOn();
        $this->config('queue', ['driver' => 'database']);

        $this->reply();

        $this->assertSame([], $this->notificationsOfUser2(), 'nothing is sent before the worker runs');
        $this->assertGreaterThan(1, $this->database()->table('queue_jobs')->count(), 'each extension queued its own job');

        $this->work();

        $this->assertCount(1, $this->notificationsOfUser2());
    }

    #[Test]
    public function each_reply_notifies_a_user_once()
    {
        $this->notifyOn();
        $this->config('queue', ['driver' => 'database']);

        $this->reply();
        $this->work();
        $this->reply();
        $this->work();

        $this->assertCount(2, $this->notificationsOfUser2());
    }

    #[Test]
    public function starting_a_discussion_notifies_a_user_once()
    {
        $this->notifyOn(Started::class, Posted::class);
        $this->config('queue', ['driver' => 'database']);

        $response = $this->send(
            $this->request('POST', '/api/discussions', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => ['title' => 'Started', 'content' => 'The first post'],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());

        $this->work();

        $this->assertCount(1, $this->notificationsOfUser2());
    }

    #[Test]
    public function the_scope_does_not_outlive_the_jobs_that_carry_it()
    {
        $this->notifyOn();
        $this->config('queue', ['driver' => 'database']);

        $this->reply();
        $this->work();

        $this->assertNull(NotificationScope::current());
    }

    #[Test]
    public function notifications_sent_outside_a_scope_are_not_limited()
    {
        $this->app();

        $syncer = $this->app()->getContainer()->make(NotificationSyncer::class);
        $post = Post::query()->find(1);

        $syncer->sync(new FollowedBlueprint($post), [User::query()->find(2)]);
        $syncer->sync(new MentionedBlueprint($post), [User::query()->find(2)]);

        $this->assertCount(2, $this->notificationsOfUser2());
    }

    #[Test]
    public function a_blueprint_claiming_again_in_a_scope_wins_its_own_claims()
    {
        $this->app();

        $scope = $this->app()->getContainer()->make(NotificationScope::class);
        $post = Post::query()->find(1);

        $this->assertSame([2], $scope->claim('scope-a', 'alert', new FollowedBlueprint($post), [2]));
        $this->assertSame([2], $scope->claim('scope-a', 'alert', new FollowedBlueprint($post), [2]), 'a retried job');
        $this->assertSame([], $scope->claim('scope-a', 'alert', new MentionedBlueprint($post), [2]));
        $this->assertSame([2], $scope->claim('scope-a', 'email', new MentionedBlueprint($post), [2]), 'another channel');
        $this->assertSame([2], $scope->claim('scope-b', 'alert', new MentionedBlueprint($post), [2]));
    }

    #[Test]
    public function one_edit_notifies_a_user_once()
    {
        $this->notifyOn(Revised::class, Revised::class);
        $this->config('queue', ['driver' => 'database']);

        $response = $this->send(
            $this->request('PATCH', '/api/posts/1', [
                'authenticatedAs' => 1,
                'json' => ['data' => ['attributes' => ['content' => 'Edited']]],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $this->work();

        $this->assertCount(1, $this->notificationsOfUser2());
    }

    #[Test]
    public function a_notification_type_the_user_muted_does_not_take_the_place_of_another()
    {
        $this->notifyOn();
        $this->config('queue', ['driver' => 'database']);
        $this->app();

        User::query()->where('id', 2)->update([
            'preferences' => json_encode([User::getNotificationPreferenceKey(FollowedBlueprint::getType(), 'alert') => false]),
        ]);

        $this->reply();
        $this->work();

        $this->assertContains(MentionedBlueprint::getType(), $this->notificationsOfUser2());
    }

    #[Test]
    public function one_per_user_limits_notifications_sent_in_its_callback()
    {
        $syncer = $this->app()->getContainer()->make(NotificationSyncer::class);
        $post = Post::query()->find(1);

        $syncer->onePerUser(function () use ($syncer, $post) {
            $syncer->sync(new FollowedBlueprint($post), [User::query()->find(2)]);
            $syncer->sync(new MentionedBlueprint($post), [User::query()->find(2)]);
        });

        $this->assertCount(1, $this->notificationsOfUser2());
    }

    #[Test]
    public function one_per_user_inside_an_action_joins_its_scope()
    {
        $this->app();

        $syncer = $this->app()->getContainer()->make(NotificationSyncer::class);
        $post = Post::query()->find(1);

        NotificationScope::run(function () use ($syncer, $post) {
            $syncer->sync(new FollowedBlueprint($post), [User::query()->find(2)]);

            $syncer->onePerUser(function () use ($syncer, $post) {
                $syncer->sync(new MentionedBlueprint($post), [User::query()->find(2)]);
            });
        });

        $this->assertCount(1, $this->notificationsOfUser2());
    }

    #[Test]
    public function claims_are_pruned_once_no_job_can_still_need_them()
    {
        $db = $this->database();

        $db->table('notification_scope_claims')->insert([
            ['scope' => 'old', 'user_id' => 2, 'channel' => 'alert', 'claimed_by' => 'x:1', 'created_at' => Carbon::now()->subDays(NotificationScope::RETENTION_DAYS + 1)],
            ['scope' => 'new', 'user_id' => 2, 'channel' => 'alert', 'claimed_by' => 'x:1', 'created_at' => Carbon::now()->subDays(NotificationScope::RETENTION_DAYS - 1)],
        ]);

        NotificationScope::prune($db);

        $this->assertSame(['new'], $db->table('notification_scope_claims')->pluck('scope')->all());
    }
}

class NotifyFollowers
{
    public function __construct(private Queue $queue)
    {
    }

    public function handle(Posted|Started|Revised $event): void
    {
        $post = $event instanceof Started ? $event->discussion->firstPost : $event->post;

        $this->queue->push(new SyncScopedNotification(FollowedBlueprint::class, $post));
    }
}

class NotifyMentioned
{
    public function __construct(private Queue $queue)
    {
    }

    public function handle(Posted|Revised $event): void
    {
        $this->queue->push(new SyncScopedNotification(MentionedBlueprint::class, $event->post));
    }
}

class SyncScopedNotification extends AbstractJob
{
    /**
     * @param class-string<FollowedBlueprint|MentionedBlueprint> $blueprint
     */
    public function __construct(private string $blueprint, private Post $post)
    {
        parent::__construct();
    }

    public function handle(NotificationSyncer $syncer): void
    {
        $syncer->sync(new $this->blueprint($this->post), [User::query()->findOrFail(2)]);
    }
}

class FollowedBlueprint implements BlueprintInterface, AlertableInterface
{
    public function __construct(public Post $post)
    {
    }

    public function getFromUser(): ?User
    {
        return null;
    }

    public function getSubject(): ?AbstractModel
    {
        return $this->post->discussion;
    }

    public function getData(): mixed
    {
        return ['postNumber' => $this->post->number];
    }

    public static function getType(): string
    {
        return 'scopeTestFollowed';
    }

    public static function getSubjectModel(): string
    {
        return Discussion::class;
    }
}

class MentionedBlueprint implements BlueprintInterface, AlertableInterface
{
    public function __construct(public Post $post)
    {
    }

    public function getFromUser(): ?User
    {
        return null;
    }

    public function getSubject(): ?AbstractModel
    {
        return $this->post;
    }

    public function getData(): mixed
    {
        return null;
    }

    public static function getType(): string
    {
        return 'scopeTestMentioned';
    }

    public static function getSubjectModel(): string
    {
        return Post::class;
    }
}
