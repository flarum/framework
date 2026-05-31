<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\notification;

use Flarum\Database\AbstractModel;
use Flarum\Locale\TranslatorInterface;
use Flarum\Notification\AlertableInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Job\SendEmailNotificationJob;
use Flarum\Notification\Job\SendNotificationsJob;
use Flarum\Notification\MailableInterface;
use Flarum\Notification\Notification;
use Flarum\Notification\NotificationMailer;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class SyncerRaceTest extends TestCase
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

    #[Test]
    public function send_notifications_job_is_idempotent_for_repeated_runs(): void
    {
        // Race scenario from #4622: under a queued driver,
        // NotificationSyncer::sync() queues a SendNotificationsJob, then
        // BEFORE that job runs the syncer is called again (e.g. from a
        // Revised event quickly following Posted). The second sync()
        // reads the DB, sees no row yet, and queues a SECOND
        // SendNotificationsJob with the same recipient. Without
        // idempotence at insert-time, both jobs run and INSERT, producing
        // duplicate rows.
        $this->app();

        $blueprint = new TestBlueprint();
        $recipient = User::find(3);

        (new SendNotificationsJob($blueprint, [$recipient]))->handle();
        (new SendNotificationsJob($blueprint, [$recipient]))->handle();
        (new SendNotificationsJob($blueprint, [$recipient]))->handle();

        $count = Notification::query()
            ->where('type', $blueprint::getType())
            ->where('user_id', $recipient->id)
            ->count();

        $this->assertEquals(1, $count, 'SendNotificationsJob is not idempotent — repeated runs created duplicate rows.');
    }

    #[Test]
    public function send_notifications_job_still_inserts_new_recipients_alongside_existing(): void
    {
        // A SendNotificationsJob may carry a mix of recipients: some that
        // already have a row (from an earlier job that ran) and some that
        // don't. The idempotence check must skip only the former and insert
        // the latter — not skip the entire batch.
        $this->app();

        $blueprint = new TestBlueprint();
        $existing = User::find(3);
        $newRecipient = User::find(2);

        // Pre-populate a row for the existing recipient.
        Notification::notify([$existing], $blueprint);

        // Run a job with both — should not duplicate the existing one, but
        // SHOULD insert a row for the new recipient.
        (new SendNotificationsJob($blueprint, [$existing, $newRecipient]))->handle();

        $existingCount = Notification::query()
            ->where('type', $blueprint::getType())
            ->where('user_id', $existing->id)
            ->count();
        $newCount = Notification::query()
            ->where('type', $blueprint::getType())
            ->where('user_id', $newRecipient->id)
            ->count();

        $this->assertEquals(1, $existingCount, 'Existing recipient was duplicated.');
        $this->assertEquals(1, $newCount, 'New recipient was not inserted.');
    }

    #[Test]
    public function send_email_notification_job_is_idempotent_for_repeated_runs(): void
    {
        // Same race, email half: the EmailNotificationDriver queues a
        // SendEmailNotificationJob per-recipient per-call to sync(). When
        // sync() is called twice before the first SendNotificationsJob has
        // run, two SendEmailNotificationJobs land in the queue for the
        // same recipient. Even after the first SendNotificationsJob
        // INSERTs the row, the second SendEmailNotificationJob still runs
        // and (without idempotence) sends a duplicate email.
        $this->app();

        $blueprint = new TestBlueprintMailable();
        $recipient = User::find(3);

        // Swap the mailer for a test double that counts send() calls.
        $mailer = new CountingNotificationMailer();
        $this->app()->getContainer()->instance(NotificationMailer::class, $mailer);

        // Pre-insert the notification row to simulate "the first job ran."
        Notification::notify([$recipient], $blueprint);

        $cache = $this->app()->getContainer()->make(\Illuminate\Contracts\Cache\Repository::class);

        (new SendEmailNotificationJob($blueprint, $recipient))->handle($mailer, $cache);
        (new SendEmailNotificationJob($blueprint, $recipient))->handle($mailer, $cache);

        $this->assertEquals(1, $mailer->sentCount, 'SendEmailNotificationJob is not idempotent — repeated runs sent duplicate emails.');
    }
}

class TestBlueprint implements BlueprintInterface, AlertableInterface
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
        return 'syncerRaceTest';
    }

    public static function getSubjectModel(): string
    {
        return 'syncerRaceTestSubjectModel';
    }
}

class TestBlueprintMailable extends TestBlueprint implements MailableInterface
{
    public static function getType(): string
    {
        return 'syncerRaceTestMailable';
    }

    public function getEmailSubject(TranslatorInterface $translator): string
    {
        return 'Test';
    }

    public function getEmailViews(): array
    {
        return ['text' => 'unused', 'html' => 'unused'];
    }
}

class CountingNotificationMailer extends NotificationMailer
{
    public int $sentCount = 0;

    public function __construct()
    {
        // Intentionally skip parent constructor — we don't need its deps.
    }

    public function send(MailableInterface&BlueprintInterface $blueprint, User $user): void
    {
        $this->sentCount++;
    }
}
