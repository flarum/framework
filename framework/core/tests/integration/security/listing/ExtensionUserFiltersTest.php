<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\security\listing;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Security: the suspend `suspended` and likes `liked` user filters (newly
 * described to the admin UI by this branch) as oracles.
 */
class ExtensionUserFiltersTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-suspend', 'flarum-likes');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'suspended', 'email' => 's@machine.local', 'is_email_confirmed' => 1, 'suspended_until' => Carbon::now()->addYear()],
                ['id' => 4, 'username' => 'liker', 'email' => 'l@machine.local', 'is_email_confirmed' => 1],
                ['id' => 6, 'username' => 'moderator', 'email' => 'm@machine.local', 'is_email_confirmed' => 1],
            ],
            'group_user' => [
                ['user_id' => 6, 'group_id' => Group::MODERATOR_ID],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'private', 'user_id' => 1, 'comment_count' => 1, 'is_private' => 1, 'first_post_id' => 1, 'last_post_id' => 1],
                ['id' => 2, 'title' => 'public', 'user_id' => 1, 'comment_count' => 1, 'first_post_id' => 2, 'last_post_id' => 2],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t>secret</t>', 'number' => 1, 'is_private' => 1],
                ['id' => 2, 'discussion_id' => 2, 'user_id' => 1, 'type' => 'comment', 'content' => '<t>pub</t>', 'number' => 1],
            ],
            'post_likes' => [
                ['post_id' => 1, 'user_id' => 4, 'created_at' => Carbon::now()],
                ['post_id' => 2, 'user_id' => 6, 'created_at' => Carbon::now()],
            ],
        ]);
    }

    private function ids(int $actor, array $filter): array
    {
        $response = $this->send(
            $this->request('GET', '/api/users', ['authenticatedAs' => $actor])->withQueryParams(['filter' => $filter, 'page' => ['limit' => 50]])
        );
        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $ids = array_map('intval', array_column(json_decode((string) $response->getBody(), true)['data'], 'id'));
        sort($ids);

        return $ids;
    }

    #[Test]
    public function suspended_filter_and_negation_are_ignored_for_members()
    {
        $all = $this->ids(2, []);
        $this->assertContains(3, $all);
        $this->assertSame($all, $this->ids(2, ['suspended' => '1']));
        $this->assertSame($all, $this->ids(2, ['-suspended' => '1']));
    }

    #[Test]
    public function suspended_filter_works_for_moderators()
    {
        $this->assertSame([3], $this->ids(6, ['suspended' => '1']));
    }

    #[Test]
    public function member_cannot_see_suspended_until_or_sort_by_it()
    {
        $response = $this->send($this->request('GET', '/api/users', ['authenticatedAs' => 2])->withQueryParams(['sort' => 'suspendedUntil']));
        $this->assertSame(400, $response->getStatusCode());

        $response = $this->send($this->request('GET', '/api/users/3', ['authenticatedAs' => 2]));
        $this->assertArrayNotHasKey('suspendedUntil', json_decode((string) $response->getBody(), true)['data']['attributes']);
    }

    #[Test]
    public function sanity_the_member_cannot_see_the_private_post()
    {
        $response = $this->send($this->request('GET', '/api/posts/1', ['authenticatedAs' => 2]));
        $this->assertSame(404, $response->getStatusCode());
    }

    /**
     * LikedFilter only counts posts visible to the actor: a member cannot learn
     * who liked a post they cannot see (nor, by enumerating ids, that it exists).
     */
    #[Test]
    public function liked_filter_does_not_reveal_likers_of_an_invisible_post()
    {
        $this->assertSame([], $this->ids(2, ['liked' => '1']), 'likers of an invisible post were disclosed');
    }

    #[Test]
    public function negated_liked_filter_on_an_invisible_post_behaves_like_a_nonexistent_post()
    {
        $this->assertSame($this->ids(2, ['-liked' => '999']), $this->ids(2, ['-liked' => '1']));
        $this->assertContains(4, $this->ids(2, ['-liked' => '1']));
    }

    #[Test]
    public function liked_filter_still_finds_likers_of_a_post_the_actor_can_see()
    {
        $this->assertSame([6], $this->ids(2, ['liked' => '2']));
        $this->assertNotContains(6, $this->ids(2, ['-liked' => '2']));
    }
}
