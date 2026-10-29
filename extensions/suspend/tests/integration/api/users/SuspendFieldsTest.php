<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend\Tests\integration\api\users;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Whether a user can be suspended is serialized with every user. Checking it
 * asks whether that user is an admin, which loads their groups; only a viewer
 * who can suspend anyone needs the answer.
 */
class SuspendFieldsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private const MODERATOR = 3;

    private const EDITORS = [5, 6, 7, 8, 9];

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-suspend');

        $users = [$this->normalUser(), ['id' => self::MODERATOR, 'username' => 'moderator', 'email' => 'moderator@machine.local', 'is_email_confirmed' => 1]];

        foreach (self::EDITORS as $editor) {
            $users[] = ['id' => $editor, 'username' => "editor$editor", 'email' => "editor$editor@machine.local", 'is_email_confirmed' => 1];
        }

        // Each post was last edited by a different user: the editors, the
        // admin and the moderator.
        $posts = [];

        foreach ([...self::EDITORS, 1, self::MODERATOR] as $i => $editor) {
            $posts[] = [
                'id' => $i + 1, 'number' => $i + 1, 'discussion_id' => 1, 'user_id' => 2, 'type' => 'comment',
                'content' => '<t><p>Post</p></t>', 'created_at' => Carbon::parse('2024-03-01'),
                'edited_at' => Carbon::parse('2024-03-02'), 'edited_user_id' => $editor,
            ];
        }

        $this->prepareDatabase([
            User::class => $users,
            Group::class => [
                ['id' => 5, 'name_singular' => 'Moderator', 'name_plural' => 'Moderators', 'is_hidden' => 0],
            ],
            'group_user' => [
                ['user_id' => self::MODERATOR, 'group_id' => 5],
            ],
            'group_permission' => [
                ['permission' => 'user.suspend', 'group_id' => 5],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Edited posts', 'created_at' => Carbon::parse('2024-03-01'), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => count($posts)],
            ],
            Post::class => $posts,
        ]);
    }

    protected function allowedRepeatedQueries(): array
    {
        return [
            // A viewer who can suspend needs to know whether each user is an admin.
            'group_user',
        ];
    }

    #[Test]
    public function viewers_who_cannot_suspend_do_not_look_up_whether_each_user_is_an_admin(): void
    {
        foreach ([null, 2] as $actor) {
            $queries = $this->listPostsWithEditors($actor)['queries'];

            $editorGroupLookups = array_filter($queries, fn (array $query) => str_contains($query['query'], 'group_user')
                && array_intersect(array_map('intval', $query['bindings']), self::EDITORS));

            $this->assertSame([], array_column($editorGroupLookups, 'query'), 'Viewer '.($actor ?? 'guest').' looked up editors\' groups.');
        }
    }

    #[Test]
    public function only_viewers_who_can_suspend_see_that_they_can(): void
    {
        foreach ([null, 2] as $actor) {
            $users = $this->listPostsWithEditors($actor)['users'];

            foreach ([...self::EDITORS, 1, self::MODERATOR] as $editor) {
                $this->assertFalse($users[$editor]['canSuspend'], 'Viewer '.($actor ?? 'guest')." can't suspend anyone.");
                $this->assertArrayNotHasKey('suspendReason', $users[$editor]);
            }
        }
    }

    #[Test]
    public function a_moderator_can_suspend_users_but_not_an_admin_or_themselves(): void
    {
        $users = $this->listPostsWithEditors(self::MODERATOR)['users'];

        foreach (self::EDITORS as $editor) {
            $this->assertTrue($users[$editor]['canSuspend'], "The moderator can suspend editor $editor.");
        }

        $this->assertFalse($users[1]['canSuspend'], "The moderator can't suspend an admin.");
        $this->assertFalse($users[self::MODERATOR]['canSuspend'], "The moderator can't suspend themselves.");
    }

    // Whatever another extension's policy allows, an admin is never suspended.
    #[Test]
    public function an_admin_cannot_be_suspended_even_when_a_policy_allows_it(): void
    {
        $this->extend((new Extend\Policy())->modelPolicy(User::class, AllowSuspendingAnyone::class));

        $this->assertSame(403, $this->suspend(actor: 2, user: 1)->getStatusCode());
        $this->assertNull(User::query()->find(1)->suspended_until);

        $this->assertSame(200, $this->suspend(actor: 2, user: 5)->getStatusCode(), 'A user who isn\'t an admin can still be suspended.');
    }

    /**
     * @return array{queries: array<array{query: string, bindings: array}>, users: array<int, array<string, mixed>>}
     */
    private function listPostsWithEditors(?int $actor): array
    {
        $this->app();

        $database = $this->database();
        $database->flushQueryLog();
        $database->enableQueryLog();

        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => $actor])
                ->withQueryParams(['filter' => ['discussion' => 1], 'include' => 'editedUser'])
        );

        $queries = $database->getQueryLog();
        $database->disableQueryLog();

        $this->assertSame(200, $response->getStatusCode());

        $users = [];

        foreach (json_decode((string) $response->getBody(), true)['included'] ?? [] as $resource) {
            if ($resource['type'] === 'users') {
                $users[(int) $resource['id']] = $resource['attributes'];
            }
        }

        return ['queries' => $queries, 'users' => $users];
    }

    private function suspend(int $actor, int $user): \Psr\Http\Message\ResponseInterface
    {
        return $this->send(
            $this->request('PATCH', "/api/users/$user", [
                'authenticatedAs' => $actor,
                'json' => ['data' => ['type' => 'users', 'attributes' => ['suspendedUntil' => Carbon::now()->addDay()]]],
            ])
        );
    }
}

class AllowSuspendingAnyone extends AbstractPolicy
{
    public function suspend(User $actor, User $user): string
    {
        return $this->allow();
    }
}
