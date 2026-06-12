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
use Flarum\Mentions\Notification\UserMentionedBlueprint;
use Flarum\Notification\Notification;
use Flarum\Post\Post;
use Flarum\Realtime\Push\Jobs\SendNotificationsJob;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class SendNotificationsJobTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-realtime', 'flarum-mentions');

        // user 2 (recipient) has TWO `userMentioned` notifications from different mentions:
        //   - one from `glowingblue` (user 4) on post 11 — this is the mention whose blueprint fires
        //   - one from `wlork` (user 3) on post 10 — an unrelated mention that is NEWER in the table
        //
        // The bug: the broadcast job selected "this user's latest notification of this type"
        // (`type` + `latest()`), which returns wlork's newer-but-unrelated notification instead of
        // the one the firing blueprint actually produced. This reproduces the reported case where a
        // mention from glowingblue surfaced a previous mention from wlork in the toast.
        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'wlork', 'email' => 'wlork@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'glowingblue', 'email' => 'glowingblue@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Hello world', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 10, 'comment_count' => 3],
            ],
            Post::class => [
                ['id' => 10, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>@you</p></t>'],
                ['id' => 11, 'number' => 2, 'discussion_id' => 1, 'created_at' => Carbon::now()->subMinute(), 'user_id' => 4, 'type' => 'comment', 'content' => '<t><p>@you</p></t>'],
            ],
            Notification::class => [
                // glowingblue's mention (post 11) — the one whose blueprint fires below. Older row.
                ['id' => 1, 'user_id' => 2, 'from_user_id' => 4, 'type' => 'userMentioned', 'subject_id' => 11, 'data' => null, 'created_at' => Carbon::now()->subMinute(), 'read_at' => null, 'is_deleted' => 0],
                // wlork's mention (post 10) — unrelated, but the NEWEST userMentioned row for this user.
                ['id' => 2, 'user_id' => 2, 'from_user_id' => 3, 'type' => 'userMentioned', 'subject_id' => 10, 'data' => null, 'created_at' => Carbon::now(), 'read_at' => null, 'is_deleted' => 0],
            ],
        ]);
    }

    #[Test]
    public function it_selects_the_notification_matching_the_fired_blueprint(): void
    {
        $this->app();

        /** @var Post $firedPost */
        $firedPost = Post::query()->findOrFail(11);
        /** @var User $recipient */
        $recipient = User::query()->findOrFail(2);

        // The blueprint that actually fired: glowingblue's mention on post 11.
        $blueprint = new UserMentionedBlueprint($firedPost);

        $job = new SendNotificationsJob($blueprint, [$recipient]);

        $selected = $job->notificationFor($recipient);

        $this->assertNotNull($selected, 'A notification should be selected for the recipient');
        $this->assertEquals(1, $selected->id, 'The broadcast must use the notification from the mention that fired (glowingblue), not the most recent unrelated one (wlork)');
        $this->assertEquals(4, $selected->from_user_id, 'from_user must be glowingblue (user 4)');
        $this->assertEquals(11, $selected->subject_id, 'subject must be the post that fired (11)');
    }
}
