<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\integration\push;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\Notification\AlertableInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Notification;
use Flarum\Realtime\Push\Jobs\SendGeneratedPayloadJob;
use Flarum\Realtime\Push\Jobs\SendNotificationsJob;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Queue\Queue;
use PHPUnit\Framework\Attributes\Test;
use Pusher\Pusher;
use ReflectionProperty;

class SendNotificationsJobTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-realtime');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'other', 'email' => 'other@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'First discussion', 'created_at' => Carbon::parse('2026-06-01 10:00:00'), 'last_posted_at' => Carbon::parse('2026-06-01 10:00:00'), 'user_id' => 3, 'comment_count' => 1],
                ['id' => 2, 'title' => 'Second discussion', 'created_at' => Carbon::parse('2026-06-01 10:00:00'), 'last_posted_at' => Carbon::parse('2026-06-01 10:00:00'), 'user_id' => 3, 'comment_count' => 1],
            ],
            Notification::class => [
                // The record for this test's blueprint (subject: discussion 1).
                // Its created_at is OLDER — as happens when the syncer restores
                // an existing record, which keeps the original timestamp.
                ['id' => 1, 'user_id' => 2, 'from_user_id' => 3, 'type' => 'discussionRenamed', 'subject_id' => 1, 'data' => null, 'created_at' => Carbon::parse('2026-06-01 10:00:00'), 'read_at' => null, 'is_deleted' => 0],
                // An unrelated notification of the SAME type with a newer timestamp.
                ['id' => 2, 'user_id' => 2, 'from_user_id' => 3, 'type' => 'discussionRenamed', 'subject_id' => 2, 'data' => null, 'created_at' => Carbon::parse('2026-06-01 12:00:00'), 'read_at' => null, 'is_deleted' => 0],
            ],
        ]);
    }

    /**
     * Runs the job with user 2 reported as connected, capturing every job
     * pushed or scheduled on the queue.
     *
     * @return array{0: array, 1: array} [pushed jobs, delayed jobs]
     */
    private function runJob(SendNotificationsJob $job): array
    {
        $container = $this->app()->getContainer();

        $pusher = $this->createStub(Pusher::class);
        $pusher->method('getChannels')->willReturn((object) ['channels' => ['private-user=2' => (object) []]]);
        $container->instance(Pusher::class, $pusher);

        $pushed = [];
        $delayed = [];

        $queue = $this->createStub(Queue::class);
        $queue->method('push')->willReturnCallback(function ($job) use (&$pushed) {
            $pushed[] = $job;

            return null;
        });
        $queue->method('later')->willReturnCallback(function ($delay, $job) use (&$delayed) {
            $delayed[] = $job;

            return null;
        });

        $job->handle($queue);

        return [$pushed, $delayed];
    }

    #[Test]
    public function pushes_the_record_matching_the_blueprint_not_the_latest_of_its_type(): void
    {
        // Boots the container and sets up the DB connection on models.
        $this->app();

        $blueprint = new RenamedStubBlueprint(Discussion::find(1), User::find(3));

        [$pushed, $delayed] = $this->runJob(new SendNotificationsJob($blueprint, [User::find(2)]));

        $this->assertCount(1, $pushed);
        $this->assertInstanceOf(SendGeneratedPayloadJob::class, $pushed[0]);

        $model = (new ReflectionProperty(SendGeneratedPayloadJob::class, 'model'))->getValue($pushed[0]);

        $this->assertInstanceOf(Notification::class, $model);
        // The blueprint concerns discussion 1, whose record is id 1 — NOT the
        // same-type record with the newer created_at (id 2).
        $this->assertSame(1, $model->id);

        $this->assertCount(0, $delayed);
    }

    #[Test]
    public function retries_when_the_record_is_not_yet_visible(): void
    {
        $this->app();

        // No record matches this blueprint (no rename of discussion 2 by user 2)
        // — as happens when the alert driver's insert job hasn't run yet.
        $blueprint = new RenamedStubBlueprint(Discussion::find(2), User::find(2));

        [$pushed, $delayed] = $this->runJob(new SendNotificationsJob($blueprint, [User::find(2)]));

        $this->assertCount(0, $pushed);
        $this->assertCount(1, $delayed);
        $this->assertInstanceOf(SendNotificationsJob::class, $delayed[0]);

        $attempt = (new ReflectionProperty(SendNotificationsJob::class, 'attempt'))->getValue($delayed[0]);

        $this->assertSame(2, $attempt);
    }

    #[Test]
    public function gives_up_after_max_attempts(): void
    {
        $this->app();

        $blueprint = new RenamedStubBlueprint(Discussion::find(2), User::find(2));

        [$pushed, $delayed] = $this->runJob(new SendNotificationsJob($blueprint, [User::find(2)], 3));

        $this->assertCount(0, $pushed);
        $this->assertCount(0, $delayed);
    }
}

class RenamedStubBlueprint implements BlueprintInterface, AlertableInterface
{
    public function __construct(
        private Discussion $discussion,
        private ?User $fromUser = null
    ) {
    }

    public function getFromUser(): ?User
    {
        return $this->fromUser;
    }

    public function getSubject(): ?AbstractModel
    {
        return $this->discussion;
    }

    public function getData(): mixed
    {
        return null;
    }

    public static function getType(): string
    {
        return 'discussionRenamed';
    }

    public static function getSubjectModel(): string
    {
        return Discussion::class;
    }
}
