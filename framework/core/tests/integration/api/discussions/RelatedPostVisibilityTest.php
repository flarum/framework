<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\discussions;

use Carbon\Carbon;
use Flarum\Api\Controller\UpdateDiscussionController;
use Flarum\Extend;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Arr;

/**
 * A discussion's firstPost, lastPost and mostRelevantPost relationships must
 * obey the same visibility scope as posts requested directly.
 */
class RelatedPostVisibilityTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $now = Carbon::now()->toDateTimeString();

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'other', 'email' => 'other@machine.local', 'is_email_confirmed' => 1],
            ],
            'discussions' => [
                // Hidden first post, visible last post.
                ['id' => 1, 'title' => 'Hidden first post', 'created_at' => $now, 'last_posted_at' => $now, 'user_id' => 2, 'first_post_id' => 1, 'last_post_id' => 2, 'comment_count' => 2, 'last_post_number' => 2],
                // Visible first post, hidden last post.
                ['id' => 2, 'title' => 'Hidden last post', 'created_at' => $now, 'last_posted_at' => $now, 'user_id' => 2, 'first_post_id' => 3, 'last_post_id' => 4, 'comment_count' => 2, 'last_post_number' => 2],
                // Private first post.
                ['id' => 3, 'title' => 'Private first post', 'created_at' => $now, 'last_posted_at' => $now, 'user_id' => 2, 'first_post_id' => 5, 'last_post_id' => 6, 'comment_count' => 2, 'last_post_number' => 2],
                // Everything visible.
                ['id' => 4, 'title' => 'Visible posts', 'created_at' => $now, 'last_posted_at' => $now, 'user_id' => 2, 'first_post_id' => 7, 'last_post_id' => 7, 'comment_count' => 1, 'last_post_number' => 1],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'created_at' => $now, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>secret hidden first post</p></t>', 'hidden_at' => $now, 'hidden_user_id' => 1],
                ['id' => 2, 'discussion_id' => 1, 'number' => 2, 'created_at' => $now, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>visible reply</p></t>'],
                ['id' => 3, 'discussion_id' => 2, 'number' => 1, 'created_at' => $now, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>visible first post</p></t>'],
                ['id' => 4, 'discussion_id' => 2, 'number' => 2, 'created_at' => $now, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>secret hidden last post</p></t>', 'hidden_at' => $now, 'hidden_user_id' => 1],
                ['id' => 5, 'discussion_id' => 3, 'number' => 1, 'created_at' => $now, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>secret private first post</p></t>', 'is_private' => 1],
                ['id' => 6, 'discussion_id' => 3, 'number' => 2, 'created_at' => $now, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>visible reply</p></t>'],
                ['id' => 7, 'discussion_id' => 4, 'number' => 1, 'created_at' => $now, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>visible first post</p></t>'],
            ],
        ]);
    }

    public function unauthorizedActors(): array
    {
        return [
            'guest' => [null],
            'unrelated member' => [3],
        ];
    }

    /**
     * @dataProvider unauthorizedActors
     * @test
     */
    public function show_does_not_expose_hidden_first_post(?int $actorId)
    {
        $json = $this->show(1, 'firstPost,lastPost', $actorId);

        $this->assertNoRelationship($json['data'], 'firstPost');
        $this->assertRelationship($json['data'], 'lastPost', '2');
        $this->assertNotIncluded($json, '1');
    }

    /**
     * @dataProvider unauthorizedActors
     * @test
     */
    public function show_does_not_expose_hidden_last_post(?int $actorId)
    {
        $json = $this->show(2, 'firstPost,lastPost', $actorId);

        $this->assertRelationship($json['data'], 'firstPost', '3');
        $this->assertNoRelationship($json['data'], 'lastPost');
        $this->assertNotIncluded($json, '4');
    }

    /**
     * @dataProvider unauthorizedActors
     * @test
     */
    public function show_does_not_expose_private_first_post(?int $actorId)
    {
        $json = $this->show(3, 'firstPost', $actorId);

        $this->assertNoRelationship($json['data'], 'firstPost');
        $this->assertNotIncluded($json, '5');
    }

    /**
     * @dataProvider unauthorizedActors
     * @test
     */
    public function index_does_not_expose_hidden_or_private_posts(?int $actorId)
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', $actorId ? ['authenticatedAs' => $actorId] : [])
                ->withQueryParams(['include' => 'firstPost,lastPost'])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getBody()->getContents(), true);
        $discussions = collect($json['data'])->keyBy('id');

        $this->assertNoRelationship($discussions['1'], 'firstPost');
        $this->assertRelationship($discussions['1'], 'lastPost', '2');
        $this->assertRelationship($discussions['2'], 'firstPost', '3');
        $this->assertNoRelationship($discussions['2'], 'lastPost');
        $this->assertNoRelationship($discussions['3'], 'firstPost');
        $this->assertRelationship($discussions['4'], 'firstPost', '7');

        foreach (['1', '4', '5'] as $postId) {
            $this->assertNotIncluded($json, $postId);
        }

        $this->assertStringNotContainsString('secret', json_encode($json));
    }

    /**
     * A relation that the controller did not eager load is loaded lazily by
     * the serializer; that load must be scoped too.
     *
     * @test
     */
    public function lazily_loaded_posts_are_scoped()
    {
        $json = $this->patchWithLazyIncludes(1, 3);

        $this->assertNoRelationship($json['data'], 'firstPost');
        $this->assertRelationship($json['data'], 'lastPost', '2');
        $this->assertNotIncluded($json, '1');

        $json = $this->patchWithLazyIncludes(2, 3);

        $this->assertRelationship($json['data'], 'firstPost', '3');
        $this->assertNoRelationship($json['data'], 'lastPost');
        $this->assertNotIncluded($json, '4');
    }

    /**
     * @test
     */
    public function lazily_loaded_posts_are_still_shown_to_those_who_can_see_them()
    {
        $json = $this->patchWithLazyIncludes(1, 2);

        $this->assertRelationship($json['data'], 'firstPost', '1');
        $this->assertRelationship($json['data'], 'lastPost', '2');
        $this->assertIncluded($json, '1');

        $json = $this->patchWithLazyIncludes(4, 3);

        $this->assertRelationship($json['data'], 'firstPost', '7');
        $this->assertIncluded($json, '7');
        // Outside a search there is no most relevant post.
        $this->assertNoRelationship($json['data'], 'mostRelevantPost');
    }

    /**
     * UpdateDiscussionController does not eager load, so any included
     * relation is loaded by the serializer.
     */
    private function patchWithLazyIncludes(int $discussionId, int $actorId): array
    {
        $this->extend(
            (new Extend\ApiController(UpdateDiscussionController::class))
                ->addOptionalInclude(['firstPost', 'lastPost', 'mostRelevantPost'])
        );

        $response = $this->send(
            $this->request('PATCH', "/api/discussions/$discussionId", [
                'authenticatedAs' => $actorId,
                'json' => ['data' => ['attributes' => ['lastReadPostNumber' => 1]]],
            ])->withQueryParams(['include' => 'firstPost,lastPost,mostRelevantPost'])
        );

        $this->assertEquals(200, $response->getStatusCode());

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @test
     */
    public function author_still_sees_own_hidden_first_post()
    {
        $json = $this->show(1, 'firstPost', 2);

        $this->assertRelationship($json['data'], 'firstPost', '1');
        $this->assertIncluded($json, '1');
    }

    /**
     * @test
     */
    public function admin_still_sees_hidden_first_post()
    {
        $json = $this->show(1, 'firstPost', 1);

        $this->assertRelationship($json['data'], 'firstPost', '1');
        $this->assertIncluded($json, '1');
    }

    /**
     * The relationship exposes a post exactly when the post endpoint does.
     *
     * @test
     */
    public function relationship_matches_direct_post_access()
    {
        foreach ([null, 1, 2, 3] as $actorId) {
            foreach ([1 => 1, 3 => 5] as $discussionId => $postId) {
                $direct = $this->send(
                    $this->request('GET', "/api/posts/$postId", $actorId ? ['authenticatedAs' => $actorId] : [])
                )->getStatusCode();

                $json = $this->show($discussionId, 'firstPost', $actorId);
                $exposed = Arr::get($json, 'data.relationships.firstPost.data') !== null;

                $this->assertEquals(
                    $direct === 200,
                    $exposed,
                    'Actor '.var_export($actorId, true)." got $direct for post $postId but ".($exposed ? 'did' : 'did not').' get it as firstPost'
                );
            }
        }
    }

    /**
     * @test
     */
    public function visible_posts_are_still_included_for_guests()
    {
        $json = $this->show(4, 'firstPost,lastPost', null);

        $this->assertRelationship($json['data'], 'firstPost', '7');
        $this->assertRelationship($json['data'], 'lastPost', '7');
        $this->assertIncluded($json, '7');
    }

    private function show(int $discussionId, string $include, ?int $actorId): array
    {
        $response = $this->send(
            $this->request('GET', "/api/discussions/$discussionId", $actorId ? ['authenticatedAs' => $actorId] : [])
                ->withQueryParams(['include' => $include])
        );

        $this->assertEquals(200, $response->getStatusCode());

        return json_decode($response->getBody()->getContents(), true);
    }

    private function assertRelationship(array $resource, string $relation, string $postId): void
    {
        $this->assertEquals(
            ['type' => 'posts', 'id' => $postId],
            Arr::get($resource, "relationships.$relation.data"),
            "Expected $relation of discussion {$resource['id']} to be post $postId"
        );
    }

    private function assertNoRelationship(array $resource, string $relation): void
    {
        $this->assertNull(
            Arr::get($resource, "relationships.$relation.data"),
            "Discussion {$resource['id']} must not expose its $relation"
        );
    }

    private function assertIncluded(array $json, string $postId): void
    {
        $this->assertContains($postId, $this->includedPostIds($json));
    }

    private function assertNotIncluded(array $json, string $postId): void
    {
        $this->assertNotContains($postId, $this->includedPostIds($json), "Post $postId must not be included");
    }

    private function includedPostIds(array $json): array
    {
        return collect($json['included'] ?? [])->where('type', 'posts')->pluck('id')->all();
    }
}
