<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Carbon\Carbon;
use Flarum\Api\Context;
use Flarum\Api\Endpoint\Index;
use Flarum\Api\Endpoint\Show;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Resource\DiscussionResource;
use Flarum\Api\Resource\UserResource;
use Flarum\Api\Schema\Relationship\ToMany;
use Flarum\Api\Sort\SortColumn;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Foundation\ValidationException;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;
use Tobyz\JsonApiServer\Schema\Field\Field;

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
            User::class => [
                $this->normalUser()
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Custom Discussion Title', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 0, 'comment_count' => 1, 'is_private' => 0],
                ['id' => 2, 'title' => 'Custom Discussion Title', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 3, 'first_post_id' => 0, 'comment_count' => 1, 'is_private' => 0],
                ['id' => 3, 'title' => 'Custom Discussion Title', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 0, 'comment_count' => 1, 'is_private' => 0],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 3, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'discussionRenamed', 'content' => '<t><p>can i haz relationz?</p></t>'],
                ['id' => 2, 'discussion_id' => 2, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'discussionRenamed', 'content' => '<t><p>can i haz relationz?</p></t>'],
                ['id' => 3, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'discussionRenamed', 'content' => '<t><p>can i haz relationz?</p></t>'],
                ['id' => 3, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'discussionRenamed', 'content' => '<t><p>can i haz relationz?</p></t>'],
            ],
        ]);
    }

    /**
     * @test
     */
    public function after_endpoint_callback_works_if_added()
    {
        $this->extend(
            (new Extend\ApiResource(DiscussionResource::class))
                ->endpoint(Show::class, function (Show $endpoint): Show {
                    return $endpoint->after(function ($context, Discussion $discussion) {
                        $discussion->title = 'dataSerializationPrepCustomTitle';

                        return $discussion;
                    });
                })
        );

        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($body = $response->getBody()->getContents(), true);

        $this->assertEquals('dataSerializationPrepCustomTitle', $payload['data']['attributes']['title'], $body);
    }

    /**
     * @test
     */
    public function after_endpoint_callback_works_with_invokable_classes()
    {
        $this->extend(
            (new Extend\ApiResource(DiscussionResource::class))
                ->endpoint(Show::class, function (Show $endpoint): Show {
                    return $endpoint->after(CustomAfterEndpointInvokableClass::class);
                })
        );

        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($body = $response->getBody()->getContents(), true);

        $this->assertEquals(CustomAfterEndpointInvokableClass::class, $payload['data']['attributes']['title'], $body);
    }

    /**
     * @test
     */
    public function after_endpoint_callback_works_if_added_to_parent_class()
    {
        $this->extend(
            (new Extend\ApiResource(AbstractDatabaseResource::class))
                ->endpoint(Show::class, function (Show $endpoint): Show {
                    return $endpoint->after(function (Context $context, object $model) {
                        if ($context->collection instanceof DiscussionResource) {
                            $model->title = 'dataSerializationPrepCustomTitle2';
                        }

                        return $model;
                    });
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
    public function after_endpoint_callback_prioritizes_child_classes()
    {
        $this->extend(
            (new Extend\ApiResource(DiscussionResource::class))
                ->endpoint(Show::class, function (Show $endpoint): Show {
                    return $endpoint->after(function (Context $context, object $model) {
                        $model->title = 'dataSerializationPrepCustomTitle4';

                        return $model;
                    });
                }),
            (new Extend\ApiResource(AbstractDatabaseResource::class))
                ->endpoint(Show::class, function (Show $endpoint): Show {
                    return $endpoint->after(function (Context $context, object $model) {
                        if ($context->collection instanceof DiscussionResource) {
                            $model->title = 'dataSerializationPrepCustomTitle3';
                        }

                        return $model;
                    });
                })
        );

        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($body = $response->getBody()->getContents(), true);

        $this->assertEquals('dataSerializationPrepCustomTitle4', $payload['data']['attributes']['title'], $body);
    }

    /**
     * @test
     */
    public function before_endpoint_callback_works_if_added_to_parent_class()
    {
        $this->extend(
            (new Extend\ApiResource(AbstractDatabaseResource::class))
                ->endpoint(Show::class, function (Show $endpoint): Show {
                    return $endpoint->before(function () {
                        throw new ValidationException(['field' => 'error on purpose']);
                    });
                })
        );

        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 1,
            ])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(422, $response->getStatusCode(), $body);
        $this->assertStringContainsString('error on purpose', $body, $body);
    }

    /**
     * @test
     */
    public function before_endpoint_callback_prioritizes_child_classes()
    {
        $this->extend(
            (new Extend\ApiResource(DiscussionResource::class))
                ->endpoint(Show::class, function (Show $endpoint): Show {
                    return $endpoint->before(function () {
                        throw new ValidationException(['field' => 'error on purpose from exact resource']);
                    });
                }),
            (new Extend\ApiResource(AbstractDatabaseResource::class))
                ->endpoint(Show::class, function (Show $endpoint): Show {
                    return $endpoint->before(function () {
                        throw new ValidationException(['field' => 'error on purpose from abstract resource']);
                    });
                })
        );

        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 1,
            ])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(422, $response->getStatusCode(), $body);
        $this->assertStringContainsString('error on purpose from abstract resource', $body, $body);
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
            (new Extend\ApiResource(UserResource::class))
                ->fields(fn () => [
                    ToMany::make('customApiControllerRelation')
                        ->type('discussions')
                        ->includable(),
                ])
                ->endpoint(Show::class, function (Show $endpoint): Show {
                    return $endpoint->addDefaultInclude(['customApiControllerRelation']);
                })
        );

        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($body = $response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customApiControllerRelation', $payload['data']['relationships'] ?? [], $body);
    }

    /**
     * @test
     */
    public function custom_relationship_optionally_included_if_added()
    {
        $this->extend(
            (new Extend\Model(User::class))
                ->hasMany('customApiControllerRelation2', Discussion::class, 'user_id'),
            (new Extend\ApiResource(UserResource::class))
                ->fields(fn () => [
                    ToMany::make('customApiControllerRelation2')
                        ->type('discussions')
                        ->includable(),
                ])
        );

        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'include' => 'customApiControllerRelation2',
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customApiControllerRelation2', $payload['data']['relationships'] ?? []);
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
            (new Extend\ApiResource(UserResource::class))
                ->endpoint(Show::class, function (Show $endpoint): Show {
                    return $endpoint->removeDefaultInclude(['groups']);
                })
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
            (new Extend\ApiResource(UserResource::class))
                ->fields(fn () => [
                    ToMany::make('customApiControllerRelation2')
                        ->type('discussions')
                        ->includable(),
                ])
                ->field('customApiControllerRelation2', fn (Field $field) => $field->includable(false))
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
            (new Extend\ApiResource(DiscussionResource::class))
                ->endpoint(Index::class, function (Index $endpoint): Index {
                    return $endpoint->limit(1);
                })
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
            (new Extend\ApiResource(DiscussionResource::class))
                ->endpoint(Index::class, function (Index $endpoint): Index {
                    return $endpoint->maxLimit(1);
                })
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
    public function custom_sort_field_exists_if_added()
    {
        $this->extend(
            (new Extend\ApiResource(DiscussionResource::class))
                ->sorts(fn () => [
                    SortColumn::make('userId')
                ]),
        );

        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'sort' => 'userId',
            ])
        );

        $payload = json_decode($body = $response->getBody()->getContents(), true);

        $this->assertEquals(200, $response->getStatusCode(), $body);
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
            (new Extend\ApiResource(DiscussionResource::class))
                ->removeSorts(['createdAt'])
        );

        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'sort' => 'createdAt',
            ])
        );

        $this->assertEquals(400, $response->getStatusCode(), $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function custom_sort_field_works_if_set()
    {
        $this->extend(
            (new Extend\ApiResource(DiscussionResource::class))
                ->sorts(fn () => [
                    SortColumn::make('userId')
                ])
                ->endpoint(Index::class, function (Index $endpoint): Index {
                    return $endpoint->defaultSort('-userId');
                })
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
            (new Extend\ApiResource(UserResource::class))
                ->endpoint(Index::class, function (Index $endpoint) use (&$users) {
                    return $endpoint->after(function ($context, $data) use (&$users) {
                        $users = $data;

                        return $data;
                    });
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
            (new Extend\ApiResource(UserResource::class))
                ->endpoint(Index::class, function (Index $endpoint) use (&$users) {
                    return $endpoint
                        ->eagerLoad('firstLevelRelation')
                        ->after(function ($context, $data) use (&$users) {
                            $users = $data;

                            return $data;
                        });
                })
        );

        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertFalse($users->filter->relationLoaded('firstLevelRelation')->isEmpty(), $response->getBody()->getContents());
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
            (new Extend\ApiResource(UserResource::class))
                ->endpoint(Index::class, function (Index $endpoint) use (&$users) {
                    return $endpoint
                        ->after(function ($context, $data) use (&$users) {
                            $users = $data;

                            return $data;
                        });
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
            (new Extend\ApiResource(UserResource::class))
                ->endpoint(Index::class, function (Index $endpoint) use (&$users) {
                    return $endpoint
                        ->eagerLoad(['firstLevelRelation.secondLevelRelation'])
                        ->after(function ($context, $data) use (&$users) {
                            $users = $data;

                            return $data;
                        });
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
            (new Extend\ApiResource(UserResource::class))
                ->endpoint(Index::class, function (Index $endpoint) use (&$users) {
                    return $endpoint
                        ->eagerLoadWhenIncluded(['firstLevelRelation' => ['secondLevelRelation']])
                        ->after(function ($context, $data) use (&$users) {
                            $users = $data;

                            return $data;
                        });
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
            (new Extend\ApiResource(UserResource::class))
                ->endpoint(Index::class, function (Index $endpoint) use (&$users) {
                    return $endpoint
                        ->eagerLoadWhere('firstLevelRelation', function ($query, $request) {})
                        ->after(function ($context, $data) use (&$users) {
                            $users = $data;

                            return $data;
                        });
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
            (new Extend\ApiResource(UserResource::class))
                ->endpoint(Index::class, function (Index $endpoint) use (&$users) {
                    return $endpoint
                        ->eagerLoad('firstLevelRelation')
                        ->eagerLoadWhere('firstLevelRelation.secondLevelRelation', function ($query, $request) {})
                        ->after(function ($context, $data) use (&$users) {
                            $users = $data;

                            return $data;
                        });
                })
        );

        $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertFalse($users->pluck('firstLevelRelation')->filter->relationLoaded('secondLevelRelation')->isEmpty());
    }
}

class CustomAfterEndpointInvokableClass
{
    public function __invoke(Context $context, Discussion $discussion): Discussion
    {
        $discussion->title = __CLASS__;

        return $discussion;
    }
}
