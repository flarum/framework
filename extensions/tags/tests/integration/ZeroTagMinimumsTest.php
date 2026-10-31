<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration;

use Flarum\Group\Group;
use Flarum\Tags\Tag;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * With no minimum number of tags, there is nothing for the tags a user can
 * see to add up to. Seeing the forum is then decided by the "View forum"
 * permission, given globally or in a restricted tag.
 */
class ZeroTagMinimumsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->setting('flarum-tags.min_primary_tags', '0');
        $this->setting('flarum-tags.min_secondary_tags', '0');

        $this->prepareDatabase([
            Tag::class => [
                ['id' => 1, 'name' => 'Open', 'slug' => 'open', 'is_primary' => true, 'position' => 0],
                ['id' => 2, 'name' => 'Restricted', 'slug' => 'restricted', 'is_primary' => true, 'position' => 1, 'is_restricted' => true],
            ],
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'insider', 'email' => 'insider@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => 5, 'name_singular' => 'Insider', 'name_plural' => 'Insiders'],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 5],
            ],
            'group_permission' => [
                ['group_id' => 5, 'permission' => 'tag2.viewForum'],
            ],
        ]);
    }

    /**
     * A private forum: only members of a group given "View forum" in the
     * restricted tag may see it.
     */
    private function forbidEveryoneElseFromSeeingTheForum(): void
    {
        $this->database()->table('group_permission')->where('permission', 'viewForum')->delete();
    }

    #[Test]
    public function guests_cannot_see_members_of_a_private_forum()
    {
        $this->forbidEveryoneElseFromSeeingTheForum();

        $response = $this->send($this->request('GET', '/api/users/2'));

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function members_without_view_forum_cannot_see_other_members_of_a_private_forum()
    {
        $this->forbidEveryoneElseFromSeeingTheForum();

        $response = $this->send($this->request('GET', '/api/users/3', ['authenticatedAs' => 2]));

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function view_forum_in_a_restricted_tag_still_opens_the_forum()
    {
        $this->forbidEveryoneElseFromSeeingTheForum();

        $response = $this->send($this->request('GET', '/api/users/2', ['authenticatedAs' => 3]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function the_global_permission_still_opens_the_forum()
    {
        $response = $this->send($this->request('GET', '/api/users/3'));

        $this->assertEquals(200, $response->getStatusCode());
    }
}
