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
use Flarum\Notification\Notification;
use Flarum\Post\Post;
use Flarum\Realtime\Push\Jobs\SendOwnStateJob;
use Flarum\Realtime\Push\Jobs\SendTriggerJob;
use Flarum\Realtime\Push\OriginSocket;
use Flarum\Realtime\Push\OwnStateSubscriber;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\NullQueue;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use Pusher\Pusher;
use ReflectionProperty;

class OwnSessionsRealtimeTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /** @var array<int, object> */
    private array $pushed = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-realtime');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(), // id 2
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Public', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 2, 'last_post_number' => 2],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>First</p></t>'],
                ['id' => 2, 'number' => 2, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Reply</p></t>'],
            ],
            Notification::class => [
                ['id' => 1, 'user_id' => 2, 'from_user_id' => 1, 'type' => 'discussionRenamed', 'subject_id' => 1, 'data' => null, 'created_at' => Carbon::now(), 'read_at' => null, 'is_deleted' => 0],
                ['id' => 2, 'user_id' => 2, 'from_user_id' => 1, 'type' => 'discussionRenamed', 'subject_id' => 1, 'data' => null, 'created_at' => Carbon::now(), 'read_at' => null, 'is_deleted' => 0],
            ],
        ]);
    }

    private function sendAs(ServerRequestInterface $request, ?string $socketId): void
    {
        $this->app();

        $pushed = &$this->pushed;
        $this->app()->getContainer()->instance(Queue::class, new class($pushed) extends NullQueue {
            /** @param array<int, object> $pushed */
            public function __construct(private array &$pushed)
            {
            }

            public function push($job, $data = '', $queue = null)
            {
                $this->pushed[] = $job;

                return null;
            }
        });

        if ($socketId !== null) {
            $request = $request->withHeader('X-Socket-ID', $socketId);
        }

        $response = $this->send($request);

        $this->assertLessThan(300, $response->getStatusCode(), (string) $response->getBody());
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T[]
     */
    private function pushedOf(string $class): array
    {
        return array_values(array_filter($this->pushed, fn ($job) => $job instanceof $class));
    }

    private function prop(object $job, string $name): mixed
    {
        return (new ReflectionProperty($job, $name))->getValue($job);
    }

    private function reply(?string $socketId): void
    {
        $this->sendAs($this->request('POST', '/api/posts', [
            'authenticatedAs' => 2,
            'json' => ['data' => ['type' => 'posts', 'attributes' => ['content' => 'From my phone'], 'relationships' => ['discussion' => ['data' => ['type' => 'discussions', 'id' => '1']]]]],
        ]), $socketId);
    }

    #[Test]
    public function a_reply_remembers_which_tab_sent_it(): void
    {
        $this->reply('111.222');

        $trigger = $this->pushedOf(SendTriggerJob::class)[0];

        $this->assertSame('111.222', $this->prop($trigger, 'originSocket'));
        $this->assertNull($this->app()->getContainer()->make(OriginSocket::class)->get());
    }

    #[Test]
    public function a_reply_without_a_socket_of_its_own_reaches_every_tab(): void
    {
        $this->reply(null);

        $this->assertNull($this->prop($this->pushedOf(SendTriggerJob::class)[0], 'originSocket'));
    }

    #[Test]
    public function a_malformed_socket_id_is_ignored(): void
    {
        $this->reply('1.2, 3.4');

        $this->assertNull($this->prop($this->pushedOf(SendTriggerJob::class)[0], 'originSocket'));
    }

    #[Test]
    public function reading_all_notifications_updates_the_other_sessions(): void
    {
        $this->sendAs($this->request('POST', '/api/notifications/read', ['authenticatedAs' => 2]), '111.222');

        [$triggered] = $this->runJob($this->pushedOf(SendOwnStateJob::class)[0]);

        $this->assertSame('private-user=2', $triggered[0]);
        $this->assertSame(OwnStateSubscriber::NOTIFICATIONS, $triggered[1]);
        $this->assertNotFalse(\DateTime::createFromFormat(DATE_ATOM, $triggered[2]['allRead']));
        $this->assertSame([0, 0], [$triggered[2]['unreadNotificationCount'], $triggered[2]['newNotificationCount']]);
        $this->assertSame(['socket_id' => '111.222'], $triggered[3]);
    }

    #[Test]
    public function deleting_all_notifications_updates_the_other_sessions(): void
    {
        $this->sendAs($this->request('DELETE', '/api/notifications', ['authenticatedAs' => 2]), null);

        [$triggered] = $this->runJob($this->pushedOf(SendOwnStateJob::class)[0]);

        $this->assertArrayHasKey('allDeleted', $triggered[2]);
        $this->assertSame(0, $triggered[2]['unreadNotificationCount']);
        $this->assertSame([], $triggered[3]);
    }

    #[Test]
    public function an_unreachable_websocket_server_is_left_alone_for_a_while(): void
    {
        $cache = $this->app()->getContainer()->make(Repository::class);
        $attempts = 0;

        $pusher = new class($attempts) extends Pusher {
            public function __construct(private int &$attempts)
            {
                parent::__construct('key', 'secret', 'app');
            }

            public function trigger($channels, string $event, $data, array $params = [], bool $already_encoded = false): object
            {
                $this->attempts++;

                throw new \Pusher\ApiErrorException('Connection refused');
            }
        };

        (new SendOwnStateJob(2, OwnStateSubscriber::NOTIFICATIONS, ['newNotificationCount' => 0]))->handle($pusher, $cache);
        (new SendOwnStateJob(2, OwnStateSubscriber::NOTIFICATIONS, ['newNotificationCount' => 0]))->handle($pusher, $cache);

        $this->assertSame(1, $attempts);
    }

    #[Test]
    public function reading_one_notification_says_which(): void
    {
        $this->sendAs($this->request('PATCH', '/api/notifications/1', [
            'authenticatedAs' => 2,
            'json' => ['data' => ['type' => 'notifications', 'id' => '1', 'attributes' => ['isRead' => true]]],
        ]), null);

        [$triggered] = $this->runJob($this->pushedOf(SendOwnStateJob::class)[0]);

        $this->assertSame(['id' => '1', 'type' => 'discussionRenamed', 'subjectId' => '1'], $triggered[2]['read']);
        // Both notifications are about the same subject, so both are read.
        $this->assertSame(0, $triggered[2]['unreadNotificationCount']);
        $this->assertSame([], $triggered[3]);
    }

    #[Test]
    public function opening_the_notification_list_clears_the_new_count_elsewhere(): void
    {
        $this->sendAs($this->request('GET', '/api/notifications', ['authenticatedAs' => 2]), '111.222');

        [$triggered] = $this->runJob($this->pushedOf(SendOwnStateJob::class)[0]);

        $this->assertSame([OwnStateSubscriber::NOTIFICATIONS, ['newNotificationCount' => 0]], [$triggered[1], $triggered[2]]);
    }

    #[Test]
    public function reading_a_discussion_updates_the_other_sessions(): void
    {
        $this->sendAs($this->request('PATCH', '/api/discussions/1', [
            'authenticatedAs' => 2,
            'json' => ['data' => ['type' => 'discussions', 'id' => '1', 'attributes' => ['lastReadPostNumber' => 2]]],
        ]), '111.222');

        [$triggered] = $this->runJob($this->pushedOf(SendOwnStateJob::class)[0]);

        $this->assertSame('private-user=2', $triggered[0]);
        $this->assertSame(OwnStateSubscriber::DISCUSSION_READ, $triggered[1]);
        $this->assertSame('1', $triggered[2]['id']);
        $this->assertSame(2, $triggered[2]['lastReadPostNumber']);
        $this->assertSame(['socket_id' => '111.222'], $triggered[3]);
    }

    #[Test]
    public function marking_everything_read_updates_the_other_sessions(): void
    {
        $this->sendAs($this->request('PATCH', '/api/users/2', [
            'authenticatedAs' => 2,
            'json' => ['data' => ['type' => 'users', 'id' => '2', 'attributes' => ['markedAllAsReadAt' => Carbon::now()->toAtomString()]]],
        ]), null);

        $jobs = array_filter(
            $this->pushedOf(SendOwnStateJob::class),
            fn (SendOwnStateJob $job) => $this->prop($job, 'event') === OwnStateSubscriber::ALL_DISCUSSIONS_READ
        );

        $this->assertCount(1, $jobs);
        $this->assertArrayHasKey('markedAllAsReadAt', $this->prop(array_values($jobs)[0], 'data'));
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: array, 3: array}>
     */
    private function runJob(SendOwnStateJob $job): array
    {
        $triggered = [];

        $job->handle(new class($triggered) extends Pusher {
            public function __construct(private array &$triggered)
            {
                parent::__construct('key', 'secret', 'app');
            }

            public function trigger($channels, string $event, $data, array $params = [], bool $already_encoded = false): object
            {
                $this->triggered[] = [$channels, $event, $data, $params];

                return (object) [];
            }
        }, $this->app()->getContainer()->make(Repository::class));

        return $triggered;
    }
}
