<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\PostResource;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * An endpoint's eager loads are written as paths from its own resource, such
 * as `user.groups` on posts. Relationships the serializer loads later, through
 * EloquentBuffer, are handed the part of each path beneath them. A relation
 * there is another resource's relation of the same name (`discussion.user` is
 * also `user`), so what it's handed must be relative to it: given whole, it
 * asks for `user` on a User. See flarum/framework#5042.
 */
class NestedEagerLoadWhereTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'starter', 'email' => 'starter@machine.local', 'is_email_confirmed' => 1],
            ],
            'group_user' => [
                ['user_id' => 2, 'group_id' => Group::MODERATOR_ID],
                ['user_id' => 3, 'group_id' => Group::MODERATOR_ID],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Started by someone else', 'user_id' => 3, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'first_post_id' => 1, 'comment_count' => 2],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'user_id' => 3, 'created_at' => Carbon::now(), 'type' => 'comment', 'content' => '<t><p>One</p></t>'],
                ['id' => 2, 'number' => 2, 'discussion_id' => 1, 'user_id' => 2, 'created_at' => Carbon::now(), 'type' => 'comment', 'content' => '<t><p>Two</p></t>'],
            ],
        ]);
    }

    #[Test]
    public function a_constrained_nested_load_does_not_break_another_relation_of_the_same_name(): void
    {
        $this->extend(
            (new Extend\ApiResource(PostResource::class))
                ->endpoint(Endpoint\Index::class, fn (Endpoint\Index $endpoint) => $endpoint
                    ->eagerLoadWhere('user.groups', fn ($query) => $query))
        );

        $body = $this->posts('user.groups,discussion.user');

        $this->assertCount(2, $body['data']);
    }

    #[Test]
    public function the_constraint_still_applies_to_the_path_it_was_registered_for(): void
    {
        $this->extend(
            (new Extend\ApiResource(PostResource::class))
                ->endpoint(Endpoint\Index::class, fn (Endpoint\Index $endpoint) => $endpoint
                    ->eagerLoadWhere('user.groups', fn ($query) => $query->whereRaw('1 = 0')))
        );

        $body = $this->posts('user.groups,discussion.user');

        $authors = collect($body['included'])->where('type', 'users')->whereIn('id', ['2', '3']);

        $this->assertNotEmpty($authors);

        foreach ($authors as $author) {
            $this->assertSame([], $author['relationships']['groups']['data'], "User {$author['id']}'s groups were constrained away.");
        }
    }

    #[Test]
    public function a_callable_nested_load_does_not_break_another_relation_of_the_same_name(): void
    {
        $this->extend(
            (new Extend\ApiResource(PostResource::class))
                ->endpoint(Endpoint\Index::class, fn (Endpoint\Index $endpoint) => $endpoint
                    ->eagerLoadWhenIncluded(['user' => ['user.groups']]))
        );

        $body = $this->posts('user,discussion.user');

        $this->assertCount(2, $body['data']);
    }

    private function posts(string $include): array
    {
        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 1])
                ->withQueryParams(['filter' => ['discussion' => '1'], 'include' => $include])
        );

        $body = (string) $response->getBody();

        $this->assertSame(200, $response->getStatusCode(), $body);

        return json_decode($body, true);
    }
}
