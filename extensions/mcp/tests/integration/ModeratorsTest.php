<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration;

use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Tags\Tag;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * What moderators are told and offered, and what members are not.
 */
class ModeratorsTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'flarum-flags', 'flarum-approval', 'flarum-lock', 'flarum-sticky', 'flarum-suspend');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'moderator', 'email' => 'moderator@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'tagmod', 'email' => 'tagmod@machine.local', 'is_email_confirmed' => 1],
                ['id' => 5, 'username' => 'approver', 'email' => 'approver@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => 5, 'name_singular' => 'Support moderator', 'name_plural' => 'Support moderators'],
                ['id' => 6, 'name_singular' => 'Approver', 'name_plural' => 'Approvers'],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => Group::MODERATOR_ID],
                ['user_id' => 4, 'group_id' => 5],
                ['user_id' => 5, 'group_id' => 6],
            ],
            'group_permission' => [
                // Support is restricted: seeing it at all takes its own permission.
                // Restricted tags take their own grants, global ones do not reach them.
                ['group_id' => Group::MODERATOR_ID, 'permission' => 'tag1.viewForum'],
                ['group_id' => Group::MODERATOR_ID, 'permission' => 'tag1.discussion.viewFlags'],
                ['group_id' => Group::MODERATOR_ID, 'permission' => 'tag1.discussion.approvePosts'],
                ['group_id' => 5, 'permission' => 'tag1.viewForum'],
                ['group_id' => 5, 'permission' => 'tag1.discussion.lock'],
                ['group_id' => 5, 'permission' => 'tag1.discussion.viewFlags'],
                ['group_id' => 6, 'permission' => 'discussion.approvePosts'],
            ],
            Tag::class => [
                ['id' => 1, 'name' => 'Support', 'slug' => 'support', 'position' => 0, 'is_primary' => true, 'is_restricted' => true],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Flagged', 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 2, 'last_post_number' => 2],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Buy cheap watches</p></t>'],
                ['id' => 2, 'discussion_id' => 1, 'number' => 2, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Waiting</p></t>', 'is_approved' => false, 'is_private' => true],
            ],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
            ],
            'flags' => [
                ['id' => 1, 'post_id' => 1, 'user_id' => 1, 'type' => 'user', 'reason' => 'spam', 'reason_detail' => 'Selling', 'created_at' => '2026-01-01 00:00:00'],
                ['id' => 2, 'post_id' => 2, 'user_id' => null, 'type' => 'approval', 'reason' => null, 'created_at' => '2026-01-02 00:00:00'],
            ],
        ]);
    }

    /**
     * @return array{you: array<string, mixed>, instructions: array<string, ?string>, core: ?string}
     */
    protected function overview(int $userId): array
    {
        $overview = json_decode($this->rpcResult($this->modern(
            'resources/read',
            ['uri' => 'flarum://forum'],
            $this->bearer($this->developerToken($userId)),
            'flarum://forum'
        ))['contents'][0]['text'], true);

        return [
            'you' => $overview['you'],
            'instructions' => array_column($overview['extensions'], 'instructions', 'id'),
            'core' => $overview['core']['instructions'] ?? null,
        ];
    }

    /**
     * @return array<string, string> ability => label
     */
    protected function abilities(int $userId): array
    {
        return array_column($this->overview($userId)['you']['can'], 'label', 'ability');
    }

    #[Test]
    public function members_are_not_told_how_to_moderate(): void
    {
        $member = $this->overview(2);

        $this->assertSame('Locked discussions take no new replies.', $member['instructions']['flarum-lock']);
        $this->assertSame('Sticky discussions stay at the top of lists.', $member['instructions']['flarum-sticky']);
        $this->assertStringNotContainsString('isApproved": true', (string) $member['instructions']['flarum-approval']);
        $this->assertNull($member['instructions']['flarum-suspend']);

        $abilities = $this->abilities(2);
        foreach (['discussion.lock', 'discussion.sticky', 'discussion.approvePosts', 'discussion.viewFlags', 'user.suspend', 'discussion.hidePosts'] as $ability) {
            $this->assertArrayNotHasKey($ability, $abilities);
        }

        $tools = $this->toolNames($this->modern('tools/list', [], $this->bearer($this->developerToken(2))));
        $this->assertNotContains('list_flags', $tools);
        $this->assertNotContains('dismiss_flags', $tools);
    }

    #[Test]
    public function moderators_are_told_and_offered_what_they_may_do(): void
    {
        $moderator = $this->overview(3);

        $this->assertStringContainsString('{"isLocked": true}', $moderator['instructions']['flarum-lock']);
        $this->assertStringContainsString('{"isSticky": true}', $moderator['instructions']['flarum-sticky']);
        $this->assertStringContainsString('{"isApproved": true}', $moderator['instructions']['flarum-approval']);
        $this->assertStringContainsString('suspendedUntil', $moderator['instructions']['flarum-suspend']);

        $abilities = $this->abilities(3);
        $this->assertSame('Lock discussions', $abilities['discussion.lock']);
        $this->assertSame('Approve posts', $abilities['discussion.approvePosts']);
        $this->assertSame('See flagged posts', $abilities['discussion.viewFlags']);
        $this->assertSame('Suspend members', $abilities['user.suspend']);
        $this->assertSame('Hide others\' posts', $abilities['discussion.hidePosts']);

        $tools = $this->toolNames($this->modern('tools/list', [], $this->bearer($this->developerToken(3))));
        $this->assertContains('list_flags', $tools);
        $this->assertContains('dismiss_flags', $tools);
    }

    #[Test]
    public function permissions_granted_in_some_tags_count(): void
    {
        $tagmod = $this->overview(4);

        $this->assertStringContainsString('{"isLocked": true}', $tagmod['instructions']['flarum-lock']);
        $this->assertSame('Sticky discussions stay at the top of lists.', $tagmod['instructions']['flarum-sticky']);

        $abilities = $this->abilities(4);
        $this->assertSame('Lock discussions (in some tags)', $abilities['discussion.lock']);
        $this->assertSame('See flagged posts (in some tags)', $abilities['discussion.viewFlags']);
        $this->assertArrayNotHasKey('discussion.sticky', $abilities);

        $this->assertContains('list_flags', $this->toolNames($this->modern('tools/list', [], $this->bearer($this->developerToken(4)))));
    }

    #[Test]
    public function a_custom_group_gets_exactly_the_moderation_it_is_given(): void
    {
        $approver = $this->overview(5);

        $this->assertStringContainsString('{"isApproved": true}', $approver['instructions']['flarum-approval']);
        $this->assertSame('Locked discussions take no new replies.', $approver['instructions']['flarum-lock']);
        $this->assertNull($approver['instructions']['flarum-suspend']);

        $abilities = $this->abilities(5);
        $this->assertSame('Approve posts', $abilities['discussion.approvePosts']);
        foreach (['discussion.lock', 'discussion.sticky', 'discussion.viewFlags', 'user.suspend', 'discussion.hidePosts'] as $ability) {
            $this->assertArrayNotHasKey($ability, $abilities);
        }

        // No flags to see, so no queue to work through.
        $this->assertNotContains('list_flags', $this->toolNames($this->modern('tools/list', [], $this->bearer($this->developerToken(5)))));
    }

    #[Test]
    public function list_flags_lists_the_moderation_queue_without_marking_it_seen(): void
    {
        $queue = $this->toolOutput($this->callTool('list_flags', [], $this->bearer($this->developerToken(3))))['data'];

        // Newest flag first; each post once, with its flags.
        $this->assertSame(['2', '1'], array_column($queue, 'postId'));
        $this->assertTrue($queue[0]['awaitingApproval']);
        $this->assertSame('approval', $queue[0]['flags'][0]['type']);
        $this->assertArrayNotHasKey('awaitingApproval', $queue[1]);
        $this->assertSame('Buy cheap watches', $queue[1]['excerpt']);
        $this->assertSame('normal', $queue[1]['author']);
        $this->assertSame(['id' => '1', 'title' => 'Flagged'], $queue[1]['discussion']);
        $this->assertSame(['type' => 'user', 'reason' => 'spam', 'detail' => 'Selling', 'by' => 'admin', 'createdAt' => '2026-01-01T00:00:00+00:00'], $queue[1]['flags'][0]);

        $this->assertNull(User::query()->findOrFail(3)->read_flags_at);

        // Flags in the tag they moderate; not the post awaiting approval, which they may not approve or see.
        $scoped = $this->toolOutput($this->callTool('list_flags', [], $this->bearer($this->developerToken(4))))['data'];
        $this->assertSame(['1'], array_column($scoped, 'postId'));
    }

    #[Test]
    public function every_note_comes_before_the_extension_list(): void
    {
        $instructions = $this->rpcResult($this->modern('server/discover', [], $this->bearer($this->developerToken(3))))['instructions'];

        $this->assertLessThanOrEqual(2000, mb_strlen($instructions));
        foreach (['{"isHidden": true}', '{"isLocked": true}', '{"isSticky": true}', '{"isApproved": true}', 'suspendedUntil'] as $note) {
            $this->assertStringContainsString($note, $instructions);
        }
        $this->assertLessThan(mb_strpos($instructions, 'Enabled extensions'), mb_strpos($instructions, 'Notes:'));
    }

    #[Test]
    public function notes_that_do_not_fit_are_dropped_whole(): void
    {
        // A long description is cut short, but still leaves less room.
        $this->setting('forum_description', str_repeat('A forum about watches. ', 60));

        $instructions = $this->rpcResult($this->modern('server/discover', [], $this->bearer($this->developerToken(3))))['instructions'];

        $this->assertLessThanOrEqual(2000, mb_strlen($instructions));
        $this->assertLessThan(400, mb_strpos($instructions, 'You are connected as'));
        $this->assertStringContainsString("\n- More in flarum://forum.\n", $instructions);
        $this->assertStringContainsString('Enabled extensions: ', $instructions);

        // Every note shown is whole.
        preg_match('/Notes:\n(.*?)\n\n/s', $instructions, $notes);
        foreach (explode("\n", $notes[1]) as $note) {
            $this->assertStringEndsWith('.', $note);
        }
    }
}
