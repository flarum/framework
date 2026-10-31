<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\integration\api;

use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * The identities of users typing while hiding their online status are carried on
 * `private-typingIdentified={id}`, separate from the discussion's own typing
 * channel. Authorization for it is what keeps a hidden user's name away from
 * anyone not entitled to it, so it must require all three of: core's
 * `user.viewLastSeenAt` (the permission that overrides `discloseOnline`
 * elsewhere), permission to see who is typing, and visibility of the discussion.
 */
class TypingIdentifiedAuthTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-realtime');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(), // id 2, Members
                // id 3, additionally in the group that may see through a hidden
                // online status.
                ['id' => 3, 'username' => 'seer', 'email' => 'seer@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => 100, 'name_singular' => 'Seer', 'name_plural' => 'Seers'],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 100],
            ],
            'group_permission' => [
                // Everyone may see who is typing; only group 100 may see through a
                // hidden online status.
                ['group_id' => Group::MEMBER_ID, 'permission' => 'discussion.flarum-realtime.view-who-types'],
                ['group_id' => 100, 'permission' => 'user.viewLastSeenAt'],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Visible', 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>x</p></t>'],
            ],
        ]);
    }

    private function authorize(string $channel, ?int $actorId): int
    {
        $options = ['json' => ['channel_name' => $channel, 'socket_id' => '123.456']];

        if ($actorId !== null) {
            $options['authenticatedAs'] = $actorId;
        }

        return $this->send(
            $this->request('POST', '/api/websocket/auth', $options)
        )->getStatusCode();
    }

    #[Test]
    public function user_with_view_last_seen_at_can_authorize_the_identified_channel(): void
    {
        $this->assertSame(200, $this->authorize('private-typingIdentified=1', 3));
    }

    #[Test]
    public function user_without_view_last_seen_at_cannot_authorize_the_identified_channel(): void
    {
        // User 2 can see the discussion and who is typing in it, but has no business
        // seeing through anyone's hidden online status.
        $this->assertSame(403, $this->authorize('private-typingIdentified=1', 2));
    }

    #[Test]
    public function guest_cannot_authorize_the_identified_channel(): void
    {
        $this->assertSame(403, $this->authorize('private-typingIdentified=1', null));
    }

    #[Test]
    public function permission_does_not_grant_access_to_an_invisible_discussion(): void
    {
        // The permission is global; discussion visibility is still checked per channel.
        $this->assertSame(403, $this->authorize('private-typingIdentified=404', 3));
    }

    #[Test]
    public function the_ordinary_typing_channel_is_unaffected(): void
    {
        // Everyone who can see the discussion keeps joining it, as before — they
        // simply receive anonymised events for users who are hiding.
        $this->assertSame(200, $this->authorize('private-typing=1', 2));
    }
}
