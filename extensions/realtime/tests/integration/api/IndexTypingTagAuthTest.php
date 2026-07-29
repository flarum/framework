<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\integration\api;

use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * The discussion-list typing dots route restricted discussions to per-tag
 * channels (`private-index-typing-tag={id}`). Authorization for those channels
 * must follow tag visibility: a user may only join the channel of a tag they
 * can see. This guards against a user receiving typing activity (even just "a
 * discussion here has activity") for a restricted tag they have no access to.
 */
class IndexTypingTagAuthTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'flarum-realtime');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(), // id 2, Members only
            ],
            Group::class => [
                ['id' => 100, 'name_singular' => 'Member viewer', 'name_plural' => 'Member viewers'],
            ],
            'group_user' => [
                ['user_id' => 2, 'group_id' => 100],
            ],
            'tags' => [
                ['id' => 1, 'name' => 'Open', 'slug' => 'open', 'is_restricted' => false],
                ['id' => 2, 'name' => 'Members only', 'slug' => 'members-only', 'is_restricted' => true],
                ['id' => 3, 'name' => 'Admin only', 'slug' => 'admin-only', 'is_restricted' => true],
            ],
            'group_permission' => [
                // Group 100 (which user 2 is in) can view tag 2, but NOT tag 3.
                ['group_id' => 100, 'permission' => 'tag2.viewForum'],
            ],
        ]);
    }

    private function authorize(int $tagId, ?int $actorId): int
    {
        $options = ['json' => ['channel_name' => "private-index-typing-tag=$tagId", 'socket_id' => '123.456']];

        if ($actorId !== null) {
            $options['authenticatedAs'] = $actorId;
        }

        return $this->send(
            $this->request('POST', '/api/websocket/auth', $options)
        )->getStatusCode();
    }

    #[Test]
    public function user_can_authorize_a_visible_restricted_tag_channel(): void
    {
        // Tag 2 is restricted but user 2's group has viewForum on it.
        $this->assertSame(200, $this->authorize(2, 2));
    }

    #[Test]
    public function user_cannot_authorize_a_hidden_restricted_tag_channel(): void
    {
        // Tag 3 is restricted and user 2 has no permission to view it.
        $this->assertSame(403, $this->authorize(3, 2));
    }

    #[Test]
    public function guest_cannot_authorize_a_restricted_tag_channel(): void
    {
        $this->assertSame(403, $this->authorize(2, null));
        $this->assertSame(403, $this->authorize(3, null));
    }
}
