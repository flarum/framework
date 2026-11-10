<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\notification;

use Flarum\Database\AbstractModel;
use Flarum\Notification\AlertableInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Event\Sent;
use Flarum\Notification\Job\SendNotificationsJob;
use Flarum\Notification\Notification;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\Attributes\Test;

class NotificationSentEventTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'recipient', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'recipient@machine.local', 'is_email_confirmed' => 1],
            ],
        ]);
    }

    /**
     * Register a Sent listener that records each event plus whether its row was queryable
     * at dispatch time, then run the given callback.
     *
     * @return array{0: Sent[], 1: bool[]} [captured events, row-existed-at-dispatch flags]
     */
    private function listenAndRun(callable $run): array
    {
        $captured = [];
        $rowExisted = [];

        $this->app()->getContainer()->make(Dispatcher::class)->listen(
            Sent::class,
            function (Sent $event) use (&$captured, &$rowExisted) {
                $captured[] = $event;
                // The whole point: the row must already exist when the event fires.
                $rowExisted[] = Notification::matchingBlueprint($event->blueprint)->exists();
            }
        );

        $run();

        return [$captured, $rowExisted];
    }

    #[Test]
    public function dispatches_sent_event_after_inserting_the_record(): void
    {
        $this->app();

        $blueprint = new SentEventTestBlueprint();
        $recipient = User::find(3);

        [$captured, $rowExisted] = $this->listenAndRun(function () use ($blueprint, $recipient) {
            (new SendNotificationsJob($blueprint, [$recipient]))->handle();
        });

        $this->assertCount(1, $captured, 'Sent should be dispatched exactly once');
        $this->assertSame($blueprint, $captured[0]->blueprint);
        $this->assertEquals([3], array_map(fn (User $u) => $u->id, $captured[0]->recipients));
        $this->assertSame([true], $rowExisted, 'The notification row must exist when Sent is dispatched');
    }

    #[Test]
    public function does_not_dispatch_when_nothing_new_is_inserted(): void
    {
        $this->app();

        $blueprint = new SentEventTestBlueprint();
        $recipient = User::find(3);

        // First run inserts the row.
        (new SendNotificationsJob($blueprint, [$recipient]))->handle();

        // Second run is an idempotent no-op (the dedup skips the already-inserted recipient),
        // so no Sent event should fire.
        [$captured] = $this->listenAndRun(function () use ($blueprint, $recipient) {
            (new SendNotificationsJob($blueprint, [$recipient]))->handle();
        });

        $this->assertCount(0, $captured, 'Sent must not fire when no new records are inserted');
    }
}

class SentEventTestBlueprint implements BlueprintInterface, AlertableInterface
{
    public function getFromUser(): ?User
    {
        return null;
    }

    public function getSubject(): ?AbstractModel
    {
        return null;
    }

    public function getData(): ?array
    {
        return null;
    }

    public static function getType(): string
    {
        return 'sentEventTest';
    }

    public static function getSubjectModel(): string
    {
        return 'sentEventTestSubjectModel';
    }
}
