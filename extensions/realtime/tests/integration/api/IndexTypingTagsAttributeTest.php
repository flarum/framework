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
 * The forum carries `flarum-realtime.index-typing-tags`: the restricted tags the
 * actor may see. The client subscribes to exactly those per-tag index-typing
 * channels, so it issues one auth round-trip per restricted tag instead of one
 * per visible tag. The attribute must follow tag visibility (never naming a
 * restricted tag the actor can't see) and must be empty when the feature is off.
 */
class IndexTypingTagsAttributeTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'flarum-realtime');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(), // id 2, in group 100
            ],
            Group::class => [
                ['id' => 100, 'name_singular' => 'Member', 'name_plural' => 'Members'],
            ],
            'group_user' => [
                ['user_id' => 2, 'group_id' => 100],
            ],
            'tags' => [
                ['id' => 1, 'name' => 'Open', 'slug' => 'open', 'is_restricted' => false],
                ['id' => 2, 'name' => 'Members', 'slug' => 'members', 'is_restricted' => true],
                ['id' => 3, 'name' => 'Admin only', 'slug' => 'admin-only', 'is_restricted' => true],
            ],
            'group_permission' => [
                // Group 100 (user 2) can view restricted tag 2, but not tag 3.
                ['group_id' => 100, 'permission' => 'tag2.viewForum'],
            ],
        ]);

        $this->setting('flarum-realtime.index-typing-indicator-restricted', '1');
    }

    /**
     * @return mixed the value of the forum attribute, or null if absent
     */
    private function indexTypingTags(?int $actorId)
    {
        $options = $actorId !== null ? ['authenticatedAs' => $actorId] : [];

        $response = $this->send($this->request('GET', '/api', $options));
        $body = json_decode($response->getBody()->getContents(), true);

        return $body['data']['attributes']['flarum-realtime.index-typing-tags'] ?? null;
    }

    #[Test]
    public function admin_sees_all_restricted_tags(): void
    {
        $this->assertEqualsCanonicalizing([2, 3], $this->indexTypingTags(1));
    }

    #[Test]
    public function member_sees_only_restricted_tags_they_can_view(): void
    {
        // Tag 2 only — never tag 3, which the member can't see. Tag 1 is excluded
        // because it isn't restricted (it rides the public channel).
        $this->assertEqualsCanonicalizing([2], $this->indexTypingTags(2));
    }

    #[Test]
    public function guest_sees_no_restricted_tags(): void
    {
        $this->assertSame([], $this->indexTypingTags(null));
    }

    #[Test]
    public function attribute_is_empty_when_the_restricted_setting_is_off(): void
    {
        $this->setting('flarum-realtime.index-typing-indicator-restricted', '0');

        $this->assertSame([], $this->indexTypingTags(1));
    }
}
