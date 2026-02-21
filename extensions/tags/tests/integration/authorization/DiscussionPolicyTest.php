<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration\authorization;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Tags\Tag;
use Flarum\Tags\Tests\integration\RetrievesRepresentativeTags;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for the tags DiscussionPolicy.
 *
 * Covers the fix for https://github.com/flarum/framework/issues/3692:
 * authors were unable to rename or hide their own discussions when those
 * discussions were in a restricted tag, because the tag restriction check
 * in `can()` would deny the ability before the author's own-ability check
 * in core's DiscussionPolicy could grant it.
 */
class DiscussionPolicyTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use RetrievesRepresentativeTags;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->prepareDatabase([
            Tag::class => $this->tags(),
            User::class => [
                $this->normalUser(),
            ],
            // Discussion in a restricted tag (tag6), owned by the normal user (id=2).
            // participant_count=1 means only the author has posted (no replies).
            Discussion::class => [
                [
                    'id' => 1,
                    'title' => 'Author discussion in restricted tag',
                    'user_id' => 2,
                    'comment_count' => 1,
                    'participant_count' => 1,
                    'created_at' => Carbon::now()->subMinutes(5)->toDateTimeString(),
                    'first_post_id' => 1,
                ],
                // Same setup but owned by the admin (id=1), so normal user is NOT the author.
                [
                    'id' => 2,
                    'title' => 'Other-author discussion in restricted tag',
                    'user_id' => 1,
                    'comment_count' => 1,
                    'participant_count' => 1,
                    'created_at' => Carbon::now()->subMinutes(5)->toDateTimeString(),
                    'first_post_id' => 2,
                ],
                // Author's discussion in an unrestricted tag.
                [
                    'id' => 3,
                    'title' => 'Author discussion in unrestricted tag',
                    'user_id' => 2,
                    'comment_count' => 1,
                    'participant_count' => 1,
                    'created_at' => Carbon::now()->subMinutes(5)->toDateTimeString(),
                    'first_post_id' => 3,
                ],
                // Author's discussion in restricted tag with replies (participant_count > 1).
                [
                    'id' => 4,
                    'title' => 'Author discussion in restricted tag with replies',
                    'user_id' => 2,
                    'comment_count' => 2,
                    'participant_count' => 2,
                    'created_at' => Carbon::now()->subMinutes(5)->toDateTimeString(),
                    'first_post_id' => 4,
                ],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p></p></t>', 'created_at' => Carbon::now()->subMinutes(5)->toDateTimeString()],
                ['id' => 2, 'discussion_id' => 2, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>', 'created_at' => Carbon::now()->subMinutes(5)->toDateTimeString()],
                ['id' => 3, 'discussion_id' => 3, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p></p></t>', 'created_at' => Carbon::now()->subMinutes(5)->toDateTimeString()],
                ['id' => 4, 'discussion_id' => 4, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p></p></t>', 'created_at' => Carbon::now()->subMinutes(5)->toDateTimeString()],
            ],
            // Place discussions 1, 2, and 4 in restricted tag6; discussion 3 in unrestricted tag1.
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 6],
                ['discussion_id' => 2, 'tag_id' => 6],
                ['discussion_id' => 3, 'tag_id' => 1],
                ['discussion_id' => 4, 'tag_id' => 6],
            ],
            // Grant the normal user (via members group) reply permission so own-ability
            // conditions that check $actor->can('reply') can pass.
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'discussion.reply'],
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // rename
    // -------------------------------------------------------------------------

    #[Test]
    public function author_can_rename_own_discussion_in_restricted_tag_when_renaming_is_indefinite()
    {
        $this->setting('allow_renaming', '-1');

        $this->app();

        $this->assertTrue(
            User::find(2)->can('rename', Discussion::find(1)),
            'Author should be able to rename when allow_renaming is indefinite (-1)'
        );
    }

    #[Test]
    #[DataProvider('withinTimeWindowProvider')]
    public function author_can_rename_own_discussion_in_restricted_tag_within_time_window(int $windowMinutes)
    {
        // Discussion was created 5 minutes ago; any window > 5 should allow.
        $this->setting('allow_renaming', (string) $windowMinutes);

        $this->app();

        $this->assertTrue(
            User::find(2)->can('rename', Discussion::find(1)),
            "Author should be able to rename within a {$windowMinutes}-minute window"
        );
    }

    public static function withinTimeWindowProvider(): array
    {
        return [
            'just inside window' => [6],
            'large window' => [60],
        ];
    }

    #[Test]
    public function author_cannot_rename_own_discussion_in_restricted_tag_when_time_window_expired()
    {
        // Discussion was created 5 minutes ago; a 3-minute window has already closed.
        $this->setting('allow_renaming', '3');

        $this->app();

        $this->assertFalse(
            User::find(2)->can('rename', Discussion::find(1)),
            'Author should not be able to rename after the time window has closed'
        );
    }

    #[Test]
    public function author_can_rename_own_discussion_in_restricted_tag_when_renaming_allowed_until_reply_and_no_replies()
    {
        $this->setting('allow_renaming', 'reply');

        $this->app();

        // Discussion 1 has participant_count=1 (no replies yet).
        $this->assertTrue(
            User::find(2)->can('rename', Discussion::find(1)),
            'Author should be able to rename when no one else has replied yet'
        );
    }

    #[Test]
    public function author_cannot_rename_own_discussion_in_restricted_tag_when_renaming_disallowed()
    {
        // Setting allow_renaming to '0' disables the own-rename ability.
        $this->setting('allow_renaming', '0');

        $this->app();

        $this->assertFalse(
            User::find(2)->can('rename', Discussion::find(1)),
            'Author should not be able to rename when allow_renaming is 0'
        );
    }

    #[Test]
    public function non_author_without_tag_permission_cannot_rename_in_restricted_tag()
    {
        $this->setting('allow_renaming', '-1');

        $this->app();

        // Discussion 2 is owned by user 1 (admin); normal user (2) is not the author.
        $this->assertFalse(
            User::find(2)->can('rename', Discussion::find(2)),
            'Non-author without tag-specific rename permission should be denied'
        );
    }

    #[Test]
    public function non_author_with_tag_permission_can_rename_in_restricted_tag()
    {
        $this->setting('allow_renaming', '-1');

        $this->prepareDatabase([
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag6.discussion.rename'],
            ],
        ]);

        $this->app();

        $this->assertTrue(
            User::find(2)->can('rename', Discussion::find(2)),
            'Non-author with tag-specific rename permission should be allowed'
        );
    }

    #[Test]
    public function author_can_rename_own_discussion_in_unrestricted_tag()
    {
        $this->setting('allow_renaming', '-1');

        $this->app();

        // Discussion 3 is in unrestricted tag1 — behaviour should be unchanged.
        $this->assertTrue(
            User::find(2)->can('rename', Discussion::find(3)),
            'Author should still be able to rename in an unrestricted tag'
        );
    }

    // -------------------------------------------------------------------------
    // hide (soft-delete own discussion)
    // -------------------------------------------------------------------------

    #[Test]
    public function author_can_hide_own_discussion_in_restricted_tag_when_no_replies()
    {
        $this->app();

        // Discussion 1: participant_count=1, not yet hidden.
        $this->assertTrue(
            User::find(2)->can('hide', Discussion::find(1)),
            'Author should be able to hide their own discussion with no replies in a restricted tag'
        );
    }

    #[Test]
    public function author_cannot_hide_own_discussion_in_restricted_tag_when_replies_exist()
    {
        $this->app();

        // Discussion 4: participant_count=2 (someone else replied).
        $this->assertFalse(
            User::find(2)->can('hide', Discussion::find(4)),
            'Author should not be able to hide their own discussion when replies exist'
        );
    }

    #[Test]
    public function non_author_without_tag_permission_cannot_hide_in_restricted_tag()
    {
        $this->app();

        // Discussion 2 is owned by user 1; normal user (2) is not the author.
        $this->assertFalse(
            User::find(2)->can('hide', Discussion::find(2)),
            'Non-author without tag-specific hide permission should be denied'
        );
    }

    #[Test]
    public function non_author_with_tag_permission_can_hide_in_restricted_tag()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag6.discussion.hide'],
            ],
        ]);

        $this->app();

        $this->assertTrue(
            User::find(2)->can('hide', Discussion::find(2)),
            'Non-author with tag-specific hide permission should be allowed'
        );
    }

    #[Test]
    public function author_can_hide_own_discussion_in_unrestricted_tag()
    {
        $this->app();

        // Discussion 3 is in unrestricted tag1.
        $this->assertTrue(
            User::find(2)->can('hide', Discussion::find(3)),
            'Author should still be able to hide their own discussion in an unrestricted tag'
        );
    }
}
