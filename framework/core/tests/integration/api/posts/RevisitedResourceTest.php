<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\posts;

use Carbon\Carbon;
use Closure;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\DiscussionResource;
use Flarum\Api\Resource\PostResource;
use Flarum\Api\Resource\UserResource;
use Flarum\Api\Schema;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * A resource is serialized once per document, however many times its include
 * paths reach it: a field hidden on the first visit is not checked again, a
 * value that visit left pending is not asked for again on the same model, and
 * a later visit still gets the relationships it includes.
 *
 * A resource reached again through another include path must not have its
 * hidden fields checked again.
 *
 * The serializer skips a field that already has a value for a resource, but a
 * field that was hidden has no value, so every later visit ran its visibility
 * check again, on whichever model instance that path held. Those checks read
 * relations (a discussion's `state` for `lastReadAt`, its `tags` through a
 * policy), and a second instance of the same discussion has none loaded, so
 * each one lazy-loaded per discussion. Post lists with their default includes
 * reach each discussion twice: as the post's `discussion` and as
 * `mentionedBy.discussion`.
 */
class RevisitedResourceTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * Below the repeated-query threshold, so a failure reports this test's own
     * assertion rather than the harness's N+1 detector.
     */
    private const DISCUSSIONS = 4;

    /**
     * Discussion id => the model instances its visibility check ran on.
     *
     * @var array<int, int[]>
     */
    private static array $checks = [];

    /**
     * User id => how many times its deferred value was asked for.
     *
     * @var array<int, int>
     */
    private static array $valueRequests = [];

    protected function setUp(): void
    {
        parent::setUp();

        self::$checks = [];
        self::$valueRequests = [];

        $discussions = [];
        $posts = [];

        for ($i = 1; $i <= self::DISCUSSIONS; $i++) {
            // Each discussion opens with a post by the admin; the normal user replies in every one.
            $discussions[] = ['id' => $i, 'title' => "Discussion $i", 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => $i, 'comment_count' => 2, 'last_post_number' => 2];
            $posts[] = ['id' => $i, 'discussion_id' => $i, 'number' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Opening post</p></t>'];
            $posts[] = ['id' => 100 + $i, 'discussion_id' => $i, 'number' => 2, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Reply</p></t>'];
        }

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => $discussions,
            Post::class => $posts,
        ]);

        // A field that is always hidden, recording each time its visibility is checked.
        $this->extend(
            (new Extend\ApiResource(DiscussionResource::class))
                ->fields(fn () => [
                    Schema\Str::make('visibilityProbe')
                        ->visible(function (Discussion $discussion): bool {
                            self::$checks[$discussion->id][] = spl_object_id($discussion);

                            return false;
                        }),
                ])
        );
    }

    public static function secondPaths(): array
    {
        return [
            // The relationship buffer loads `discussion.firstPost` and points
            // each first post back at the listed discussion: the second path
            // holds the same instance.
            'loaded by the serializer' => [false],
            // An endpoint eager load fills the whole path with fresh models,
            // as extensions do under their includes (mentions' `mentionedBy`
            // loads its own `discussion`): the second path holds another
            // instance of the same discussion, with none of its relations.
            'pre-loaded by an endpoint eager load' => [true],
        ];
    }

    #[Test]
    #[DataProvider('secondPaths')]
    public function a_hidden_field_is_checked_once_per_resource(bool $preloaded): void
    {
        if ($preloaded) {
            $this->extend(
                (new Extend\ApiResource(PostResource::class))
                    ->endpoint(Endpoint\Index::class, function (Endpoint\Index $endpoint): Endpoint\Index {
                        return $endpoint->eagerLoadWhenIncluded([
                            'discussion.firstPost.discussion' => ['discussion.firstPost.discussion'],
                        ]);
                    })
            );
        }

        // Each discussion is reached twice: as the reply's `discussion`, and
        // as that discussion's first post's `discussion`.
        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 1])
                ->withQueryParams([
                    'filter' => ['author' => 'normal'],
                    'include' => 'discussion,discussion.firstPost,discussion.firstPost.discussion',
                ])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $document = json_decode($body, true);

        $this->assertCount(self::DISCUSSIONS, $document['data']);

        $included = collect($document['included'] ?? [])->groupBy('type');

        // The document does reach every discussion through both paths...
        $this->assertEqualsCanonicalizing(
            array_map('strval', range(1, self::DISCUSSIONS)),
            $included['discussions']->pluck('id')->all()
        );

        foreach ($included['discussions'] as $discussion) {
            $this->assertSame($discussion['id'], $discussion['relationships']['firstPost']['data']['id'] ?? null);
            $this->assertArrayNotHasKey('visibilityProbe', $discussion['attributes']);
        }

        foreach ($included['posts'] as $firstPost) {
            $this->assertSame($firstPost['id'], $firstPost['relationships']['discussion']['data']['id'] ?? null);
        }

        // ...and decides once per discussion that the field is hidden.
        $checks = array_map('count', self::$checks);
        ksort($checks);

        $this->assertSame(
            array_fill_keys(range(1, self::DISCUSSIONS), 1),
            $checks,
            'A hidden field\'s visibility was checked again each time its discussion was reached through another include path.'
        );
    }

    #[Test]
    public function a_resource_reached_again_still_gets_what_that_visit_includes(): void
    {
        // The discussion's author also posted last, so the same user is reached
        // as `lastPostedUser`, which includes nothing of it, and as `user`,
        // which includes its groups. Whichever is reached first, the groups
        // are included: only a field's visibility carries over between visits.
        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 50, 'title' => 'Discussion 50', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 2, 'last_posted_user_id' => 2, 'first_post_id' => 500, 'last_post_id' => 500, 'comment_count' => 1, 'last_post_number' => 1],
            ],
            Post::class => [
                ['id' => 500, 'discussion_id' => 50, 'number' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Opening post</p></t>'],
            ],
            'group_user' => [
                ['user_id' => 2, 'group_id' => 4],
            ],
        ]);

        $response = $this->send(
            $this->request('GET', '/api/discussions/50', ['authenticatedAs' => 1])
                ->withQueryParams(['include' => 'user.groups,lastPostedUser'])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $included = collect(json_decode($body, true)['included'] ?? []);

        $user = $included->first(fn (array $resource) => $resource['type'] === 'users' && $resource['id'] === '2');

        $this->assertNotNull($user, 'The author is included.');
        $this->assertSame([['type' => 'groups', 'id' => '4']], $user['relationships']['groups']['data'] ?? null, 'The author lost the groups this request includes.');
        $this->assertNotNull($included->first(fn (array $resource) => $resource['type'] === 'groups' && $resource['id'] === '4'), 'The author\'s group is included.');
    }

    #[Test]
    public function a_pending_value_is_asked_for_once_per_model(): void
    {
        // A value resolved later, as relationship counts and other batched
        // values are. Every reply's `user` is the same model of its author,
        // so the author is reached once per reply.
        $this->extend(
            (new Extend\ApiResource(UserResource::class))
                ->fields(fn () => [
                    Schema\Str::make('deferredProbe')
                        ->get(function (User $user): Closure {
                            self::$valueRequests[$user->id] = (self::$valueRequests[$user->id] ?? 0) + 1;

                            return fn () => 'resolved';
                        }),
                ])
        );

        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 1])
                ->withQueryParams([
                    'filter' => ['author' => 'normal'],
                    'include' => 'user',
                ])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $document = json_decode($body, true);

        $this->assertCount(self::DISCUSSIONS, $document['data']);

        $author = collect($document['included'] ?? [])->first(fn (array $resource) => $resource['type'] === 'users' && $resource['id'] === '2');

        $this->assertSame('resolved', $author['attributes']['deferredProbe'] ?? null);
        $this->assertSame([2 => 1], self::$valueRequests, 'The author\'s pending value was asked for again each time a reply reached it.');
    }
}
