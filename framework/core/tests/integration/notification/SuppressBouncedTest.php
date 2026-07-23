<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\notification;

use Carbon\Carbon;
use Flarum\Locale\TranslatorInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Extend;
use Flarum\Notification\Driver\EmailNotificationDriver;
use Flarum\Notification\MailableInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Queue\Queue;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;

/**
 * Proves the bounce suppression gate in EmailNotificationDriver: when
 * `mail_suppress_bounced` is on, users with a recorded bounce are skipped;
 * when off (the default), behaviour is unchanged.
 */
class SuppressBouncedTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        // Register the test notification type with email enabled by default,
        // so shouldEmail() is true unless the suppression gate skips the user.
        $this->extend(
            (new Extend\Notification())
                ->type(SuppressTestBlueprint::class, ['email'])
        );

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                // A user whose address has hard-bounced.
                [
                    'id' => 3,
                    'username' => 'bounced',
                    'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim',
                    'email' => 'bounced@machine.local',
                    'is_email_confirmed' => 1,
                    'email_bounced_at' => Carbon::now(),
                    'email_bounce_reason' => 'No such mailbox',
                ],
            ],
        ]);
    }

    private function driveWith(bool $suppress): int
    {
        $this->setting('mail_suppress_bounced', $suppress);

        $pushCount = 0;
        $queue = m::mock(Queue::class);
        $queue->shouldReceive('push')->andReturnUsing(function () use (&$pushCount) {
            $pushCount++;

            return null;
        });

        $driver = new EmailNotificationDriver(
            $queue,
            $this->app()->getContainer()->make(SettingsRepositoryInterface::class),
        );

        // A bounced user (3) and a healthy user (2). Email is on by default for
        // this registered type, so the ONLY thing that can skip the bounced
        // user is the suppression gate.
        $driver->send(new SuppressTestBlueprint(), [User::find(3), User::find(2)]);

        return $pushCount;
    }

    #[Test]
    public function bounced_user_is_skipped_when_suppression_is_enabled(): void
    {
        // Only the healthy user's email job should be queued.
        $this->assertEquals(1, $this->driveWith(suppress: true));
    }

    #[Test]
    public function bounced_user_is_still_emailed_when_suppression_is_disabled(): void
    {
        // Default behaviour: both users get a job, bounce state ignored.
        $this->assertEquals(2, $this->driveWith(suppress: false));
    }
}

class SuppressTestBlueprint implements BlueprintInterface, MailableInterface
{
    public function getFromUser(): ?User
    {
        return null;
    }

    public function getSubject(): ?\Flarum\Database\AbstractModel
    {
        return null;
    }

    public function getData(): ?array
    {
        return null;
    }

    public static function getType(): string
    {
        return 'suppressTest';
    }

    public static function getSubjectModel(): string
    {
        return 'suppressTestSubjectModel';
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
