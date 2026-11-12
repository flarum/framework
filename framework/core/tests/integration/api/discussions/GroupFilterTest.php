<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\discussions;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class GroupFilterTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        // Discussion n is started by user n; discussion 5's author was deleted.
        $authors = [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => null];
        $discussions = [];
        $posts = [];

        foreach ($authors as $id => $userId) {
            $date = Carbon::createFromDate(2000 + $id, 1, 1)->toDateTimeString();
            $discussions[] = ['id' => $id, 'title' => "Discussion $id", 'created_at' => $date, 'last_posted_at' => $date, 'user_id' => $userId, 'first_post_id' => $id, 'comment_count' => 1];
            $posts[] = ['id' => $id, 'discussion_id' => $id, 'created_at' => $date, 'user_id' => $userId, 'type' => 'comment', 'content' => '<t><p>Hello</p></t>'];
        }

        $this->prepareDatabase([
            Discussion::class => $discussions,
            Post::class => $posts,
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'moderator', 'email' => 'moderator@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'agent', 'email' => 'agent@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => 10, 'name_singular' => 'Agent', 'name_plural' => 'Agents', 'is_hidden' => 1],
                ['id' => 11, 'name_singular' => 'Équipe', 'name_plural' => 'Équipes', 'is_hidden' => 0],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => Group::MODERATOR_ID],
                ['user_id' => 4, 'group_id' => 10],
                ['user_id' => 2, 'group_id' => 11],
            ],
        ]);
    }

    protected function filter(array $filter, ?int $actor = 1): array
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', $actor ? ['authenticatedAs' => $actor] : [])
                ->withQueryParams(['filter' => $filter])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        return Arr::pluck(json_decode($body, true)['data'], 'id');
    }

    public static function groupIdentifiers(): array
    {
        return [
            'id' => ['4'],
            'singular name' => ['mod'],
            'plural name' => ['mods'],
            'name in another case' => ['Mods'],
        ];
    }

    #[Test]
    #[DataProvider('groupIdentifiers')]
    public function shows_discussions_started_by_members_of_a_group(string $group)
    {
        $this->assertEquals(['3'], $this->filter(['group' => $group]));
    }

    public static function accentedNames(): array
    {
        return [
            'as stored' => ['Équipe'],
            'lowercase' => ['équipe'],
            'uppercase plural' => ['ÉQUIPES'],
        ];
    }

    #[Test]
    #[DataProvider('accentedNames')]
    public function matches_names_outside_ascii_in_any_case(string $group)
    {
        $this->assertEquals(['2'], $this->filter(['group' => $group]));
    }

    #[Test]
    public function shows_discussions_started_by_members_of_any_listed_group()
    {
        $this->assertEqualsCanonicalizing(['1', '3'], $this->filter(['group' => 'mods,admins']));
    }

    #[Test]
    public function negated_shows_discussions_not_started_by_members()
    {
        $this->assertEqualsCanonicalizing(['1', '2', '4', '5'], $this->filter(['-group' => 'mods']));
    }

    #[Test]
    public function members_group_means_every_registered_author()
    {
        $this->assertEqualsCanonicalizing(['1', '2', '3', '4'], $this->filter(['group' => 'members']));
        $this->assertEquals(['5'], $this->filter(['-group' => 'members']));
    }

    #[Test]
    public function unknown_group_matches_nothing()
    {
        $this->assertEquals([], $this->filter(['group' => 'nobody']));
        $this->assertEquals([], $this->filter(['group' => 'guests']));
    }

    #[Test]
    public function hidden_group_works_for_actors_who_can_see_it()
    {
        $this->assertEquals(['4'], $this->filter(['group' => 'agents']));
    }

    #[Test]
    public function hidden_group_does_not_reveal_its_members()
    {
        $this->assertEquals([], $this->filter(['group' => 'agents'], 2));
        $this->assertEquals([], $this->filter(['group' => '10'], null));
        $this->assertEqualsCanonicalizing(['1', '2', '3', '4', '5'], $this->filter(['-group' => 'agents'], 2));
    }

    #[Test]
    public function combines_with_other_filters()
    {
        $this->assertEquals(['3'], $this->filter(['group' => 'mods,admins', '-author' => 'admin']));
    }
}
