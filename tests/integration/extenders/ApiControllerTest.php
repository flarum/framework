<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Carbon\Carbon;
use Flarum\Api\Controller\AbstractShowController;
use Flarum\Api\Controller\ListDiscussionsController;
use Flarum\Api\Controller\ListUsersController;
use Flarum\Api\Controller\ShowDiscussionController;
use Flarum\Api\Controller\ShowForumController;
use Flarum\Api\Controller\ShowPostController;
use Flarum\Api\Controller\ShowUserController;
use Flarum\Api\Serializer\DiscussionSerializer;
use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Api\Serializer\PostSerializer;
use Flarum\Api\Serializer\UserSerializer;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;

class ApiControllerTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->normalUser()
            ],
            'discussions' => [
                ['id' => 1, 'title' => 'Custom Discussion Title', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 0, 'comment_count' => 1, 'is_private' => 0],
                ['id' => 2, 'title' => 'Custom Discussion Title', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 3, 'first_post_id' => 0, 'comment_count' => 1, 'is_private' => 0],
                ['id' => 3, 'title' => 'Custom Discussion Title', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 0, 'comment_count' => 1, 'is_private' => 0],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 3, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'discussionRenamed', 'content' => '<t><p>can i haz relationz?</p></t>'],
                ['id' => 2, 'discussion_id' => 2, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'discussionRenamed', 'content' => '<t><p>can i haz relationz?</p></t>'],
                ['id' => 3, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'discussionRenamed', 'content' => '<t><p>can i haz relationz?</p></t>'],
            ],
        ]);
    }

    /**
     * @test
     */
    public function prepare_data_serialization_callback_works_if_added()
    {
        $this->extend(
            (new Extend\ApiController(ShowDiscussionController::class))
                ->prepareDataForSerialization(function ($controller, Discussion $discussion) {
                    $discussion->title = 'dataSerializationPrepCustomTitle';
                })
        );

        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals('dataSerializationPrepCustomTitle', $payload['data']['attributes']['title']);
    }

    /**
     * @test
     */
    public function prepare_data_serialization_callback_works_with_invokable_classes()
    {
        $this->extend(
            (new Extend\ApiController(ShowDiscussionController::class))
                ->prepareDataForSerialization(CustomPrepareDataSerializationInvokableClass::class)
        );

        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(CustomPrepareDataSerializationInvokableClass::class, $payload['data']['attributes']['title']);
    }

    /**
     * @test
     */
    public function prepare_data_serialization_allows_passing_args_by_reference_with_closures()
    {
        $this->extend(
            (new Extend\ApiSerializer(ForumSerializer::class))
                ->hasMany('referenceTest', UserSerializer::class),
            (new Extend\ApiController(ShowForumController::class))
                ->addInclude('referenceTest')
                ->prepareDataForSerialization(function ($controller, &$data) {
                    $data['referenceTest'] = User::limit(2)->get();
                })
        );

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('referenceTest', $payload['data']['relationships']);
    }

    /**
     * @test
     */
    public function prepare_data_serialization_allows_passing_args_by_reference_with_invokable_classes()
    {
        $this->extend(
            (new Extend\ApiSerializer(ForumSerializer::class))
                ->hasMany('referenceTest2', UserSerializer::class),
            (new Extend\ApiController(ShowForumController::class))
                ->addInclude('referenceTest2')
                ->prepareDataForSerialization(CustomInvokableClassArgsReference::class)
        );

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('referenceTest2', $payload['data']['relationships']);
    }

    /**
     * @test
     */
    public function prepare_data_serialization_callback_works_if_added_to_parent_class()
    {
        $this->extend(
            (new Extend\ApiController(AbstractShowController::class))
                ->prepareDataForSerialization(function ($controller, Discussion $discussion) {
                    if ($controller instanceof ShowDiscussionController) {
                        $discussion->title = 'dataSerializationPrepCustomTitle2';
                    }
                })
        );

        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals('dataSerializationPrepCustomTitle2', $payload['data']['attributes']['title']);
    }

    /**
     * @test
     */
    public function prepare_data_serialization_callback_prioritizes_child_classes()
    {
        $this->extend(
            (new Extend\ApiController(AbstractShowController::class))
                ->prepareDataForSerialization(function ($controller, Discussion $discussion) {
                    if ($controller instanceof ShowDiscussionController) {
                        $discussion->title = 'dataSerializationPrepCustomTitle3';
                    }
                }),
            (new Extend\ApiController(ShowDiscussionController::class))
                ->prepareDataForSerialization(function ($controller, Discussion $discussion) {
                    $discussion->title = 'dataSerializationPrepCustomTitle4';
                })
        );

        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals('dataSerializationPrepCustomTitle4', $payload['data']['attributes']['title']);
    }

    /**
     * @test
     */
    public function prepare_data_query_callback_works_if_added_to_parent_class()
    {
        $this->extend(
            (new Extend\ApiController(AbstractShowController::class))
                ->prepareDataQuery(function ($controller) {
                    if ($controller instanceof ShowDiscussionController) {
                        $controller->setSerializer(CustomDiscussionSerializer2::class);
                    }
                })
        );

        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customSerializer2', $payload['data']['attributes']);
    }

    /**
     * @test
     */
    public function prepare_data_query_callback_prioritizes_child_classes()
    {
        $this->extend(
            (new Extend\ApiController(AbstractShowController::class))
                ->prepareDataForSerialization(function ($controller) {
                    if ($controller instanceof ShowDiscussionController) {
                        $controller->setSerializer(CustomDiscussionSerializer2::class);
                    }
                }),
            (new Extend\ApiController(ShowDiscussionController::class))
                ->prepareDataForSerialization(function ($controller) {
                    $controller->setSerializer(CustomDiscussionSerializer::class);
                })
        );

        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customSerializer', $payload['data']['attributes']);
    }

    /**
     * @test
     */
    public function custom_serializer_doesnt_work_by_default()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayNotHasKey('customSerializer', $payload['data']['attributes']);
    }

    /**
     * @test
     */
    public function custom_serializer_works_if_set()
    {
        $this->extend(
            (new Extend\ApiController(ShowDiscussionController::class))
                ->setSerializer(CustomDiscussionSerializer::class)
        );

        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customSerializer', $payload['data']['attributes']);
    }

    /**
     * @test
     */
    public function custom_serializer_works_if_set_with_invokable_class()
    {
        $this->extend(
            (new Extend\ApiController(ShowPostController::class))
                ->setSerializer(CustomPostSerializer::class, CustomApiControllerInvokableClass::class)
        );
        $this->prepareDatabase([
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>'],
            ],
        ]);

        $response = $this->send(
            $this->request('GET', '/api/posts/1', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customSerializer', $payload['data']['attributes']);
    }

    /**
     * @test
     */
    public function custom_serializer_doesnt_work_with_false_callback_return()
    {
        $this->extend(
            (new Extend\ApiController(ShowUserController::class))
                ->setSerializer(CustomUserSerializer::class, function () {
                    return false;
                })
        );

        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayNotHasKey('customSerializer', $payload['data']['attributes']);
    }

    /**
     * @test
     */
    public function custom_relationship_not_included_by_default()
    {
        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayNotHasKey('customApiControllerRelation', $payload['data']['relationships']);
        $this->assertArrayNotHasKey('customApiControllerRelation2', $payload['data']['relationships']);
    }

    /**
     * @test
     */
    public function custom_relationship_included_if_added()
    {
        $this->extend(
            (new Extend\Model(User::class))
                ->hasMany('customApiControllerRelation', Discussion::class, 'user_id'),
            (new Extend\ApiSerializer(UserSerializer::class))
                ->hasMany('customApiControllerRelation', DiscussionSerializer::class),
            (new Extend\ApiController(ShowUserController::class))
                ->addInclude('customApiControllerRelation')
        );

        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customApiControllerRelation', $payload['data']['relationships']);
    }

    /**
     * @test
     */
    public function custom_relationship_optionally_included_if_added()
    {
        $this->extend(
            (new Extend\Model(User::class))
                ->hasMany('customApiControllerRelation2', Discussion::class, 'user_id'),
            (new Extend\ApiSerializer(UserSerializer::class))
                ->hasMany('customApiControllerRelation2', DiscussionSerializer::class),
            (new Extend\ApiController(ShowUserController::class))
                ->addOptionalInclude('customApiControllerRelation2')
        );

        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'include' => 'customApiControllerRelation2',
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customApiControllerRelation2', $payload['data']['relationships']);
    }

    /**
     * @test
     */
    public function custom_relationship_included_by_default()
    {
        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('groups', $payload['data']['relationships']);
    }

    /**
     * @test
     */
    public function custom_relationship_not_included_if_removed()
    {
        $this->extend(
            (new Extend\ApiController(ShowUserController::class))
                ->removeInclude('groups')
        );

        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayNotHasKey('groups', Arr::get($payload, 'data.relationships', []));
    }

    /**
     * @test
     */
    public function custom_relationship_not_optionally_included_if_removed()
    {
        $this->extend(
            (new Extend\Model(User::class))
                ->hasMany('customApiControllerRelation2', Discussion::class, 'user_id'),
            (new Extend\ApiSerializer(UserSerializer::class))
                ->hasMany('customApiControllerRelation2', DiscussionSerializer::class),
            (new Extend\ApiController(ShowUserController::class))
                ->addOptionalInclude('customApiControllerRelation2')
                ->removeOptionalInclude('customApiControllerRelation2')
        );

        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'include' => 'customApiControllerRelation2',
            ])
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function custom_limit_doesnt_work_by_default()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertCount(3, $payload['data']);
    }

    /**
     * @test
     */
    public function custom_limit_works_if_set()
    {
        $this->extend(
            (new Extend\ApiController(ListDiscussionsController::class))
                ->setLimit(1)
        );

        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertCount(1, $payload['data']);
    }

    /**
     * @test
     */
    public function custom_max_limit_works_if_set()
    {
        $this->extend(
            (new Extend\ApiController(ListDiscussionsController::class))
                ->setMaxLimit(1)
        );

        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'page' => ['limit' => '5'],
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertCount(1, $payload['data']);
    }

    /**
     * @test
     */
    public function custom_sort_field_doesnt_exist_by_default()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'sort' => 'userId',
            ])
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function custom_sort_field_doesnt_work_with_false_callback_return()
    {
        $this->extend(
            (new Extend\ApiController(ListDiscussionsController::class))
                ->addSortField('userId', function () {
                    return false;
                })
        );

        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'sort' => 'userId',
            ])
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function custom_sort_field_exists_if_added()
    {
        $this->extend(
            (new Extend\ApiController(ListDiscussionsController::class))
                ->addSortField('userId')
        );

        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'sort' => 'userId',
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([3, 1, 2], Arr::pluck($payload['data'], 'id'));
    }

    /**
     * @test
     */
    public function custom_sort_field_exists_by_default()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'sort' => 'createdAt',
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function custom_sort_field_doesnt_exist_if_removed()
    {
        $this->extend(
            (new Extend\ApiController(ListDiscussionsController::class))
                ->removeSortField('createdAt')
        );

        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'sort' => 'createdAt',
            ])
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function custom_sort_field_works_if_set()
    {
        $this->extend(
            (new Extend\ApiController(ListDiscussionsController::class))
                ->addSortField('userId')
                ->setSort(['userId' => 'desc'])
        );

        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([2, 1, 3], Arr::pluck($payload['data'], 'id'));
    }

    /**
     * @test
     */
    public function custom_first_level_relation_is_not_loaded_by_default()
    {
        $users = null;

        $this->extend(
            (new Extend\Model(User::class))
                ->hasOne('firstLevelRelation', Post::class, 'user_id'),
            (new Extend\ApiController(ListUsersController::class))
                ->prepareDataForSerialization(function ($controller, $data) use (&$users) {
                    $users = $data;

                    return [];
                })
        );

        $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertTrue($users->filter->relationLoaded('firstLevelRelation')->isEmpty());
    }

    /**
     * @test
     */
    public function custom_first_level_relation_is_loaded_if_added()
    {
        $users = null;

        $this->extend(
            (new Extend\Model(User::class))
                ->hasOne('firstLevelRelation', Post::class, 'user_id'),
            (new Extend\ApiController(ListUsersController::class))
                ->load('firstLevelRelation')
                ->prepareDataForSerialization(function ($controller, $data) use (&$users) {
                    $users = $data;

                    return [];
                })
        );

        $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertFalse($users->filter->relationLoaded('firstLevelRelation')->isEmpty());
    }

    /**
     * @test
     */
    public function custom_second_level_relation_is_not_loaded_by_default()
    {
        $users = null;

        $this->extend(
            (new Extend\Model(User::class))
                ->hasOne('firstLevelRelation', Post::class, 'user_id'),
            (new Extend\Model(Post::class))
                ->belongsTo('secondLevelRelation', Discussion::class),
            (new Extend\ApiController(ListUsersController::class))
                ->prepareDataForSerialization(function ($controller, $data) use (&$users) {
                    $users = $data;

                    return [];
                })
        );

        $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertTrue($users->pluck('firstLevelRelation')->filter->relationLoaded('secondLevelRelation')->isEmpty());
    }

    /**
     * @test
     */
    public function custom_second_level_relation_is_loaded_if_added()
    {
        $users = null;

        $this->extend(
            (new Extend\Model(User::class))
                ->hasOne('firstLevelRelation', Post::class, 'user_id'),
            (new Extend\Model(Post::class))
                ->belongsTo('secondLevelRelation', Discussion::class),
            (new Extend\ApiController(ListUsersController::class))
                ->load(['firstLevelRelation', 'firstLevelRelation.secondLevelRelation'])
                ->prepareDataForSerialization(function ($controller, $data) use (&$users) {
                    $users = $data;

                    return [];
                })
        );

        $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertFalse($users->pluck('firstLevelRelation')->filter->relationLoaded('secondLevelRelation')->isEmpty());
    }

    /**
     * @test
     */
    public function custom_second_level_relation_is_not_loaded_when_first_level_is_not()
    {
        $users = null;

        $this->extend(
            (new Extend\Model(User::class))
                ->hasOne('firstLevelRelation', Post::class, 'user_id'),
            (new Extend\Model(Post::class))
                ->belongsTo('secondLevelRelation', Discussion::class),
            (new Extend\ApiController(ListUsersController::class))
                ->load(['firstLevelRelation.secondLevelRelation'])
                ->prepareDataForSerialization(function ($controller, $data) use (&$users) {
                    $users = $data;

                    return [];
                })
        );

        $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertTrue($users->pluck('firstLevelRelation')->filter->relationLoaded('secondLevelRelation')->isEmpty());
    }

    /**
     * @test
     */
    public function custom_callable_first_level_relation_is_loaded_if_added()
    {
        $users = null;

        $this->extend(
            (new Extend\Model(User::class))
                ->hasOne('firstLevelRelation', Post::class, 'user_id'),
            (new Extend\ApiController(ListUsersController::class))
                ->loadWhere('firstLevelRelation', function ($query, $request) {})
                ->prepareDataForSerialization(function ($controller, $data) use (&$users) {
                    $users = $data;

                    return [];
                })
        );

        $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertFalse($users->filter->relationLoaded('firstLevelRelation')->isEmpty());
    }

    /**
     * @test
     */
    public function custom_callable_second_level_relation_is_loaded_if_added()
    {
        $users = null;

        $this->extend(
            (new Extend\Model(User::class))
                ->hasOne('firstLevelRelation', Post::class, 'user_id'),
            (new Extend\Model(Post::class))
                ->belongsTo('secondLevelRelation', Discussion::class),
            (new Extend\ApiController(ListUsersController::class))
                ->load('firstLevelRelation')
                ->loadWhere('firstLevelRelation.secondLevelRelation', function ($query, $request) {})
                ->prepareDataForSerialization(function ($controller, $data) use (&$users) {
                    $users = $data;

                    return [];
                })
        );

        $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertFalse($users->pluck('firstLevelRelation')->filter->relationLoaded('secondLevelRelation')->isEmpty());
    }

    /**
     * @test
     */
    public function custom_callable_second_level_relation_is_not_loaded_when_first_level_is_not()
    {
        $users = null;

        $this->extend(
            (new Extend\Model(User::class))
                ->hasOne('firstLevelRelation', Post::class, 'user_id'),
            (new Extend\Model(Post::class))
                ->belongsTo('secondLevelRelation', Discussion::class),
            (new Extend\ApiController(ListUsersController::class))
                ->loadWhere('firstLevelRelation.secondLevelRelation', function ($query, $request) {})
                ->prepareDataForSerialization(function ($controller, $data) use (&$users) {
                    $users = $data;

                    return [];
                })
        );

        $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertTrue($users->pluck('firstLevelRelation')->filter->relationLoaded('secondLevelRelation')->isEmpty());
    }

    /**
     * @test
     */
    public function custom_callable_second_level_relation_is_loaded_when_first_level_is()
    {
        $users = null;

        $this->extend(
            (new Extend\Model(User::class))
                ->hasOne('firstLevelRelation', Post::class, 'user_id'),
            (new Extend\Model(Post::class))
                ->belongsTo('secondLevelRelation', Discussion::class),
            (new Extend\ApiController(ListUsersController::class))
                ->loadWhere('firstLevelRelation', function ($query, $request) {})
                ->loadWhere('firstLevelRelation.secondLevelRelation', function ($query, $request) {})
                ->prepareDataForSerialization(function ($controller, $data) use (&$users) {
                    $users = $data;

                    return [];
                })
        );

        $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertTrue($users->pluck('firstLevelRelation')->filter->relationLoaded('secondLevelRelation')->isNotEmpty());
    }
}

class CustomDiscussionSerializer extends DiscussionSerializer
{
    protected function getDefaultAttributes($discussion)
    {
        return parent::getDefaultAttributes($discussion) + [
            'customSerializer' => true
        ];
    }
}

class CustomDiscussionSerializer2 extends DiscussionSerializer
{
    protected function getDefaultAttributes($discussion)
    {
        return parent::getDefaultAttributes($discussion) + [
            'customSerializer2' => true
        ];
    }
}

class CustomUserSerializer extends UserSerializer
{
    protected function getDefaultAttributes($user)
    {
        return parent::getDefaultAttributes($user) + [
            'customSerializer' => true
        ];
    }
}

class CustomPostSerializer extends PostSerializer
{
    protected function getDefaultAttributes($post)
    {
        return parent::getDefaultAttributes($post) + [
            'customSerializer' => true
        ];
    }
}

class CustomApiControllerInvokableClass
{
    public function __invoke()
    {
        return true;
    }
}

class CustomPrepareDataSerializationInvokableClass
{
    public function __invoke(ShowDiscussionController $controller, Discussion $discussion)
    {
        $discussion->title = __CLASS__;
    }
}

class CustomInvokableClassArgsReference
{
    public function __invoke($controller, &$data)
    {
        $data['referenceTest2'] = User::limit(2)->get();
    }
}
