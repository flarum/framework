<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\notification;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Notification\Blueprint\DiscussionRenamedBlueprint;
use Flarum\Notification\NotificationSyncer;
use Flarum\Post\DiscussionRenamedPost;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * The unread and new notification counts are cached, so withdrawing or
 * restoring a notification through the syncer must forget them.
 */
class SyncerCountCacheTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Renamed', 'user_id' => 1, 'comment_count' => 1, 'created_at' => Carbon::now()],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Hello</p></t>', 'number' => 1, 'created_at' => Carbon::now()],
                ['id' => 2, 'discussion_id' => 1, 'user_id' => 2, 'type' => 'discussionRenamed', 'content' => '["Original","Renamed"]', 'number' => 2, 'created_at' => Carbon::now()],
            ],
            'notifications' => [
                ['id' => 1, 'user_id' => 1, 'type' => 'discussionRenamed', 'subject_id' => 1, 'from_user_id' => 2, 'data' => '{"postNumber":2}', 'read_at' => null, 'created_at' => Carbon::now()],
            ],
        ]);
    }

    #[Test]
    public function syncing_a_recipient_away_updates_their_counts(): void
    {
        $this->assertSame([1, 1], $this->counts());

        $this->syncer()->sync($this->blueprint(), []);

        $this->assertSame([0, 0], $this->counts());
    }

    #[Test]
    public function syncing_a_recipient_back_updates_their_counts(): void
    {
        $this->syncer()->sync($this->blueprint(), []);
        $this->assertSame([0, 0], $this->counts());

        $this->syncer()->sync($this->blueprint(), [User::query()->find(1)]);

        $this->assertSame([1, 1], $this->counts());
    }

    #[Test]
    public function deleting_updates_the_recipients_counts(): void
    {
        $this->assertSame([1, 1], $this->counts());

        $this->syncer()->delete($this->blueprint());

        $this->assertSame([0, 0], $this->counts());
    }

    #[Test]
    public function restoring_updates_the_recipients_counts(): void
    {
        $this->syncer()->delete($this->blueprint());
        $this->assertSame([0, 0], $this->counts());

        $this->syncer()->restore($this->blueprint());

        $this->assertSame([1, 1], $this->counts());
    }

    private function syncer(): NotificationSyncer
    {
        return $this->app()->getContainer()->make(NotificationSyncer::class);
    }

    private function blueprint(): DiscussionRenamedBlueprint
    {
        $this->app();

        return new DiscussionRenamedBlueprint(DiscussionRenamedPost::query()->find(2));
    }

    /**
     * @return array{int, int} The admin's unread and new notification counts.
     */
    private function counts(): array
    {
        $this->app();

        $user = User::query()->find(1);

        return [$user->getUnreadNotificationCount(), $user->getNewNotificationCount()];
    }
}
