<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Deck\Tests\integration;

use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class AuthorGroupFilterTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-deck');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'secret', 'email' => 'secret@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => 10, 'name_singular' => 'Helper', 'name_plural' => 'Helpers', 'is_hidden' => 0],
                ['id' => 11, 'name_singular' => 'Agent', 'name_plural' => 'Agents', 'is_hidden' => 1],
            ],
            'group_user' => [
                ['user_id' => 2, 'group_id' => 10],
                ['user_id' => 3, 'group_id' => 11],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Chat', 'created_at' => '2026-01-01 10:00:00', 'last_posted_at' => '2026-01-01 13:00:00', 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 3],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'created_at' => '2026-01-01 10:00:00', 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>By an admin</p></t>'],
                ['id' => 2, 'discussion_id' => 1, 'number' => 2, 'created_at' => '2026-01-01 11:00:00', 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>By a helper</p></t>'],
                ['id' => 3, 'discussion_id' => 1, 'number' => 3, 'created_at' => '2026-01-01 12:00:00', 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>By an agent</p></t>'],
            ],
        ]);
    }

    public function test_posts_by_members_of_a_group(): void
    {
        $this->assertEquals(['2'], $this->postIds('10', 2));
        $this->assertEquals(['1'], $this->postIds((string) Group::ADMINISTRATOR_ID, 2));
    }

    public function test_members_means_every_registered_user(): void
    {
        $this->assertEqualsCanonicalizing(['1', '2', '3'], $this->postIds((string) Group::MEMBER_ID, 1));
    }

    public function test_a_hidden_group_reveals_nothing_to_those_who_cannot_see_it(): void
    {
        $this->assertEquals([], $this->postIds('11', 2));
    }

    public function test_a_hidden_group_works_for_those_who_can_see_it(): void
    {
        $this->assertEquals(['3'], $this->postIds('11', 1));
    }

    protected function postIds(string $groupId, int $actorId): array
    {
        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => $actorId])
                ->withQueryParams(['filter' => ['authorGroup' => $groupId, 'type' => 'comment']])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        return array_column(json_decode($body, true)['data'], 'id');
    }
}
