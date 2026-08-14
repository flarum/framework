<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\queue;

use Flarum\Extend;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Driver\EmailNotificationDriver;
use Flarum\Notification\Job\SendEmailNotificationJob;
use Flarum\Notification\MailableInterface;
use Flarum\Notification\NotificationSyncer;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * The email notification driver must push jobs through the routing queue, so a
 * job routed (e.g. by fof/horizon) onto the `mail` queue actually lands there
 * instead of falling through to `default`.
 *
 * Regression: the driver is constructed at boot by NotificationServiceProvider,
 * which captured whatever queue was bound at that instant. Because the
 * RoutingQueue wrapper is applied in QueueServiceProvider::boot() and
 * NotificationServiceProvider booted first, the long-lived driver held the raw,
 * unwrapped connection — so every notification email bypassed routing and was
 * pushed to `default` (tries=1), tombstoning on any worker recycle.
 */
class NotificationQueueRoutingTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    /**
     * End to end: with SendEmailNotificationJob routed onto `mail`, sending a
     * mailable notification through the registered driver must enqueue the job
     * on `mail`, not `default`. A recording queue is installed as the underlying
     * connection so the routed queue name can be asserted without depending on a
     * physical jobs table.
     *
     * This is the core regression guard. With the boot-order bug, the driver
     * captured the connection at construction — before the RoutingQueue wrapper
     * was applied — so the route was never consulted and the job was pushed with
     * no queue (falling through to `default`) instead of `mail`.
     */
    #[Test]
    public function sending_a_mailable_notification_routes_the_job_onto_its_queue(): void
    {
        $recorder = new RecordingQueue();
        RecordingQueueServiceProvider::$recorder = $recorder;

        $this->extend(
            (new Extend\Queue())->route(SendEmailNotificationJob::class, 'mail'),
            (new Extend\ServiceProvider())->register(RecordingQueueServiceProvider::class),
            // Register the mailable blueprint with the email driver enabled by
            // default, so the recipient's shouldEmail() is true and a job is pushed.
            (new Extend\Notification())->type(TestMailableBlueprint::class, ['email'])
        );

        $this->app();

        /** @var EmailNotificationDriver $email */
        $email = NotificationSyncer::getNotificationDrivers()['email'];

        $email->send(new TestMailableBlueprint(), [User::find(1)]);

        $this->assertCount(1, $recorder->pushed, 'Exactly one notification email job should be pushed.');
        $this->assertSame(
            'mail',
            $recorder->pushed[0]['queue'],
            'The notification email job must be routed onto the `mail` queue, not fall through to `default`.'
        );
    }

    /**
     * Installs without any queue routing (a plain database or Redis queue with
     * no Horizon) must be unaffected: with no route registered, the job is pushed
     * with no explicit queue and falls through to the driver's own default —
     * exactly as before this fix.
     */
    #[Test]
    public function without_a_route_the_job_uses_the_driver_default_queue(): void
    {
        $recorder = new RecordingQueue();
        RecordingQueueServiceProvider::$recorder = $recorder;

        $this->extend(
            // No Extend\Queue route registered.
            (new Extend\ServiceProvider())->register(RecordingQueueServiceProvider::class),
            (new Extend\Notification())->type(TestMailableBlueprint::class, ['email'])
        );

        $this->app();

        /** @var EmailNotificationDriver $email */
        $email = NotificationSyncer::getNotificationDrivers()['email'];

        $email->send(new TestMailableBlueprint(), [User::find(1)]);

        $this->assertCount(1, $recorder->pushed, 'The notification email job should still be pushed.');
        $this->assertNull(
            $recorder->pushed[0]['queue'],
            'With no route, the job must be pushed with no explicit queue so the driver default applies.'
        );
    }

    /**
     * On a sync-queue install there is no separate queue to route onto; the
     * connection is intentionally left unwrapped (a bare SyncQueue) so jobs run
     * inline on push and `instanceof SyncQueue` detection keeps working. The
     * driver resolves this same connection, so nothing about routing interferes
     * with sync installs. (Inline execution itself is covered by the existing
     * notification integration tests.).
     */
    #[Test]
    public function sync_queue_connection_stays_unwrapped_for_the_driver(): void
    {
        // No queue config → the default sync driver.
        $this->app();

        $queue = $this->app()->getContainer()->make(\Illuminate\Contracts\Queue\Queue::class);

        // A bare SyncQueue (not a RoutingQueue) confirms the connection is left
        // unwrapped, so inline execution and SyncQueue detection keep working.
        $this->assertInstanceOf(
            \Illuminate\Queue\SyncQueue::class,
            $queue,
            'The sync connection must stay unwrapped so inline execution and SyncQueue detection keep working; '.
            'the driver resolves this same connection at push time.'
        );
    }
}

/**
 * A minimal async-style queue that records what it was asked to push, so the
 * routed queue name can be asserted. It is not a SyncQueue, so the
 * QueueServiceProvider wraps it in a RoutingQueue exactly as a real driver.
 */
class RecordingQueue extends \Illuminate\Queue\Queue implements \Illuminate\Contracts\Queue\Queue
{
    /** @var array<int, array{job: mixed, queue: ?string}> */
    public array $pushed = [];

    public function push($job, $data = '', $queue = null)
    {
        $this->pushed[] = ['job' => $job, 'queue' => $queue];

        return null;
    }

    public function pushRaw($payload, $queue = null, array $options = [])
    {
        return null;
    }

    public function later($delay, $job, $data = '', $queue = null)
    {
        $this->pushed[] = ['job' => $job, 'queue' => $queue];

        return null;
    }

    public function pop($queue = null)
    {
        return null;
    }

    public function size($queue = null)
    {
        return 0;
    }

    public function pendingSize($queue = null)
    {
        return 0;
    }

    public function delayedSize($queue = null)
    {
        return 0;
    }

    public function reservedSize($queue = null)
    {
        return 0;
    }

    public function creationTimeOfOldestPendingJob($queue = null)
    {
        return null;
    }

    public function getConnectionName()
    {
        return 'recording';
    }

    public function setConnectionName($name)
    {
        return $this;
    }
}

class RecordingQueueServiceProvider extends \Flarum\Foundation\AbstractServiceProvider
{
    public static ?RecordingQueue $recorder = null;

    public function register(): void
    {
        $this->container->extend('flarum.queue.connection', function ($queue, $container) {
            $recorder = static::$recorder;
            $recorder->setContainer($container);

            return $recorder;
        });
    }
}

class TestMailableBlueprint implements BlueprintInterface, MailableInterface
{
    public function getFromUser(): ?User
    {
        return null;
    }

    public function getSubject(): ?\Flarum\Database\AbstractModel
    {
        return null;
    }

    public function getData(): mixed
    {
        return null;
    }

    public static function getType(): string
    {
        return 'testMailable';
    }

    public static function getSubjectModel(): string
    {
        return User::class;
    }

    public function getEmailViews(): array
    {
        return ['text' => 'flarum.forum::default', 'html' => 'flarum.forum::default'];
    }

    public function getEmailSubject(\Flarum\Locale\TranslatorInterface $translator): string
    {
        return 'Test';
    }
}
