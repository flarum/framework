<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration\api\discussions;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Tags\Tag;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * A permission granted in a restricted tag only grants that permission, not
 * every other one whose name happens to contain it: "Hide posts"
 * (discussion.hidePosts) is not "Delete discussions" (discussion.hide).
 */
class RestrictedTagPermissionNamesTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->prepareDatabase([
            Tag::class => [
                ['id' => 1, 'name' => 'Restricted', 'slug' => 'restricted', 'is_primary' => true, 'position' => 0, 'is_restricted' => true],
            ],
            User::class => [
                ['id' => 3, 'username' => 'hides_posts', 'email' => 'hides_posts@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'hides_discussions', 'email' => 'hides_discussions@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => 5, 'name_singular' => 'Post hider', 'name_plural' => 'Post hiders'],
                ['id' => 6, 'name_singular' => 'Discussion hider', 'name_plural' => 'Discussion hiders'],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 5],
                ['user_id' => 4, 'group_id' => 6],
            ],
            'group_permission' => [
                ['group_id' => 5, 'permission' => 'tag1.viewForum'],
                ['group_id' => 5, 'permission' => 'tag1.discussion.hidePosts'],
                ['group_id' => 6, 'permission' => 'tag1.viewForum'],
                ['group_id' => 6, 'permission' => 'tag1.discussion.hide'],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Hidden', 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1, 'created_at' => Carbon::now(), 'hidden_at' => Carbon::now(), 'hidden_user_id' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'created_at' => Carbon::now()],
            ],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
            ],
        ]);
    }

    #[Test]
    public function hiding_posts_in_a_restricted_tag_does_not_reveal_its_hidden_discussions()
    {
        $response = $this->send($this->request('GET', '/api/discussions/1', ['authenticatedAs' => 3]));

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function hiding_discussions_in_a_restricted_tag_reveals_its_hidden_discussions()
    {
        $response = $this->send($this->request('GET', '/api/discussions/1', ['authenticatedAs' => 4]));

        $this->assertEquals(200, $response->getStatusCode());
    }
}
