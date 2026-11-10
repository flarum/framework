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
use Flarum\Notification\Notification;
use Flarum\Notification\NotificationSyncer;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class MatchingBlueprintTest extends TestCase
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
    public function matching_blueprint_finds_existing_row_with_null_data(): void
    {
        $this->app();

        $blueprint = new TestNotificationWithoutData();

        Notification::notify([User::find(3)], $blueprint);

        $found = Notification::matchingBlueprint($blueprint)->first();

        $this->assertNotNull($found, 'matchingBlueprint() did not find the inserted row.');
        $this->assertNull($found->data);
    }

    #[Test]
    public function matching_blueprint_finds_existing_row_with_non_null_data(): void
    {
        // Regression test for #4643: on MySQL, the dedup query compared the
        // JSON column directly to the json_encode'd string, but MySQL
        // canonicalises stored JSON (`{"replyNumber": 58}`) and the encoded
        // input is compact (`{"replyNumber":58}`) — string equality never
        // matched, so every NotificationSyncer::sync() call on a blueprint
        // with non-null data created a duplicate row.
        $this->app();

        $blueprint = new TestNotificationWithData();

        Notification::notify([User::find(3)], $blueprint);

        $found = Notification::matchingBlueprint($blueprint)->first();

        $this->assertNotNull($found, 'matchingBlueprint() did not find the inserted row.');
    }

    #[Test]
    public function syncer_does_not_create_duplicate_rows_on_repeated_sync_with_non_null_data(): void
    {
        // The user-visible symptom of #4643: editing a post that produced a
        // notification (postMentioned, newPost, etc.) re-runs sync() with
        // the same blueprint, and the broken matchingBlueprint() makes the
        // syncer treat the recipient as new every time.
        $syncer = $this->app()->getContainer()->make(NotificationSyncer::class);
        $blueprint = new TestNotificationWithData();
        $recipients = [User::find(3)];

        $syncer->sync($blueprint, $recipients);
        $syncer->sync($blueprint, $recipients);
        $syncer->sync($blueprint, $recipients);

        $count = Notification::query()
            ->where('type', $blueprint::getType())
            ->where('user_id', 3)
            ->count();

        $this->assertEquals(1, $count, 'Repeated sync() with the same blueprint produced duplicate rows.');
    }
}

class TestNotificationWithData implements BlueprintInterface, AlertableInterface
{
    public function getFromUser(): ?User
    {
        return null;
    }

    public function getSubject(): ?AbstractModel
    {
        return null;
    }

    public function getData(): array
    {
        return ['replyNumber' => 58];
    }

    public static function getType(): string
    {
        return 'testWithData';
    }

    public static function getSubjectModel(): string
    {
        return 'testWithDataSubjectModel';
    }
}

class TestNotificationWithoutData implements BlueprintInterface, AlertableInterface
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
        return 'testWithoutData';
    }

    public static function getSubjectModel(): string
    {
        return 'testWithoutDataSubjectModel';
    }
}
