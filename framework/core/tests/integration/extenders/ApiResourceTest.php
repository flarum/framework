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
use Flarum\Api\Resource\ForumResource;
use Flarum\Api\Resource\UserResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Foundation\ValidationException;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Database\PostgresConnection;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;
use Tobyz\JsonApiServer\Schema\Field\Field;

class ApiResourceTest extends TestCase
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
                ['id' => 1, 'title' => 'Custom Discussion Title', 'created_at' => Carbon::now()->addMinutes(1)->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 0, 'comment_count' => 1, 'is_private' => 0],
                ['id' => 2, 'title' => 'Custom Discussion Title', 'created_at' => Carbon::now()->addMinutes(2)->toDateTimeString(), 'user_id' => 3, 'first_post_id' => 0, 'comment_count' => 1, 'is_private' => 0],
                ['id' => 3, 'title' => 'Custom Discussion Title', 'created_at' => Carbon::now()->addMinutes(3)->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 0, 'comment_count' => 1, 'is_private' => 0],

                ['id' => 4, 'title' => 'Custom Discussion Title', 'created_at' => Carbon::now()->addMinutes(4)->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 0, 'comment_count' => 1, 'is_private' => 0],
                ['id' => 5, 'title' => 'Custom Discussion Title', 'created_at' => Carbon::now()->addMinutes(5)->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 0, 'comment_count' => 1, 'is_private' => 0],
                ['id' => 6, 'title' => 'Custom Discussion Title', 'created_at' => Carbon::now()->addMinutes(6)->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 0, 'comment_count' => 1, 'is_private' => 0],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 3, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'discussionRenamed', 'content' => '<t><p>can i haz relationz?</p></t>'],
                ['id' => 2, 'discussion_id' => 2, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'discussionRenamed', 'content' => '<t><p>can i haz relationz?</p></t>'],
                ['id' => 3, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'discussionRenamed', 'content' => '<t><p>can i haz relationz?</p></t>'],
                ['id' => 3, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'discussionRenamed', 'content' => '<t><p>can i haz relationz?</p></t>'],

                ['id' => 5, 'discussion_id' => 6, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'discussionRenamed', 'content' => '<t><p>can i haz relationz?</p></t>'],
            ],
        ]);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function custom_relationship_included_if_added()
    {
        $this->extend(
            (new Extend\Model(User::class))
                ->hasMany('customApiControllerRelation', Discussion::class, 'user_id'),
            (new Extend\ApiResource(UserResource::class))
                ->fields(fn () => [
                    Schema\Relationship\ToMany::make('customApiControllerRelation')
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

    #[Test]
    public function custom_relationship_optionally_included_if_added()
    {
        $this->extend(
            (new Extend\Model(User::class))
                ->hasMany('customApiControllerRelation2', Discussion::class, 'user_id'),
            (new Extend\ApiResource(UserResource::class))
                ->fields(fn () => [
                    Schema\Relationship\ToMany::make('customApiControllerRelation2')
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function custom_relationship_not_optionally_included_if_removed()
    {
        $this->extend(
            (new Extend\Model(User::class))
                ->hasMany('customApiControllerRelation2', Discussion::class, 'user_id'),
            (new Extend\ApiResource(UserResource::class))
                ->fields(fn () => [
                    Schema\Relationship\ToMany::make('customApiControllerRelation2')
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

    #[Test]
    public function custom_limit_doesnt_work_by_default()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertCount(6, $payload['data']);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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
        $this->assertEquals([3, 1, 4, 5, 6, 2], Arr::pluck($payload['data'], 'id'));
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

        if ($this->database() instanceof PostgresConnection) {
            $this->assertEquals([2, 1, 4, 5, 6, 3], Arr::pluck($payload['data'], 'id'));
        } else {
            $this->assertEquals([2, 6, 5, 4, 1, 3], Arr::pluck($payload['data'], 'id'));
        }
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function custom_attributes_dont_exist_by_default()
    {
        $this->app();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayNotHasKey('customAttribute', $payload['data']['attributes']);
    }

    #[Test]
    public function custom_attributes_exist_if_added()
    {
        $this->extend(
            (new Extend\ApiResource(ForumResource::class))
                ->fields(fn () => [
                    Schema\Boolean::make('customAttribute')
                        ->get(fn () => true),
                ])
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customAttribute', $payload['data']['attributes']);
    }

    #[Test]
    public function custom_attributes_exist_if_added_before_field()
    {
        $this->extend(
            (new Extend\ApiResource(DiscussionResource::class))
                ->fieldsBefore('title', fn () => [
                    Schema\Boolean::make('customAttribute')
                        ->writable()
                        ->set(fn (Discussion $discussion) => $this->assertNull($discussion->title))
                ])
        );

        $response = $this->send(
            $this->request('POST', '/api/discussions', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'discussions',
                        'attributes' => [
                            'title' => 'Custom Discussion Title',
                            'customAttribute' => true,
                            'content' => 'Custom Discussion Content',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode(), $response->getBody()->getContents());
    }

    #[Test]
    public function custom_attributes_exist_if_added_after_field()
    {
        $this->extend(
            (new Extend\ApiResource(DiscussionResource::class))
                ->fieldsAfter('title', fn () => [
                    Schema\Boolean::make('customAttribute')
                        ->writable()
                        ->set(fn (Discussion $discussion) => $this->assertNotNull($discussion->title))
                ])
        );

        $response = $this->send(
            $this->request('POST', '/api/discussions', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'discussions',
                        'attributes' => [
                            'title' => 'Custom Discussion Title',
                            'customAttribute' => true,
                            'content' => 'Custom Discussion Content',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode(), $response->getBody()->getContents());
    }

    #[Test]
    public function custom_attributes_with_invokable_exist_if_added()
    {
        $this->extend(
            (new Extend\ApiResource(ForumResource::class))
                ->fields(CustomAttributesInvokableClass::class)
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customAttributeFromInvokable', $payload['data']['attributes']);
    }

    #[Test]
    public function custom_attributes_exist_if_added_to_parent_class()
    {
        $this->extend(
            (new Extend\ApiResource(AbstractDatabaseResource::class))
                ->fields(fn () => [
                    Schema\Boolean::make('customAttribute')
                        ->get(fn () => true),
                ])
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customAttribute', $payload['data']['attributes']);
    }

    #[Test]
    public function custom_attributes_prioritize_child_classes()
    {
        $this->extend(
            (new Extend\ApiResource(AbstractDatabaseResource::class))
                ->fields(fn () => [
                    Schema\Str::make('customAttribute')
                        ->get(fn () => 'initialValue')
                ]),
            (new Extend\ApiResource(UserResource::class))
                ->fields(fn () => [
                    Schema\Str::make('customAttribute')
                        ->get(fn () => 'newValue')
                ]),
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('customAttribute', $payload['data']['attributes']);
        $this->assertEquals('newValue', $payload['data']['attributes']['customAttribute']);
    }

    #[Test]
    public function custom_attributes_can_be_overridden()
    {
        $this->extend(
            (new Extend\ApiResource(UserResource::class))
                ->fields(fn () => [
                    Schema\Str::make('someCustomAttribute')
                        ->get(fn () => 'newValue'),
                ])
                ->fields(fn () => [
                    Schema\Str::make('someCustomAttribute')
                        ->get(fn () => 'secondValue'),
                    Schema\Str::make('someOtherCustomAttribute')
                        ->get(fn () => 'secondValue'),
                ])
                ->fields(fn () => [
                    Schema\Str::make('someOtherCustomAttribute')
                        ->get(fn () => 'newValue'),
                ])
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody()->getContents(), true);

        $this->assertArrayHasKey('someCustomAttribute', $payload['data']['attributes']);
        $this->assertEquals('secondValue', $payload['data']['attributes']['someCustomAttribute']);
        $this->assertArrayHasKey('someOtherCustomAttribute', $payload['data']['attributes']);
        $this->assertEquals('newValue', $payload['data']['attributes']['someOtherCustomAttribute']);
    }

    #[Test]
    public function custom_relations_dont_exist_by_default()
    {
        $this->extend(
            (new Extend\ApiResource(UserResource::class))
                ->endpoint(Show::class, function (Show $endpoint): Show {
                    return $endpoint->addDefaultInclude(['customSerializerRelation', 'postCustomRelation', 'anotherCustomRelation']);
                })
        );

        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    #[Test]
    public function custom_hasMany_relationship_exists_if_added()
    {
        $this->extend(
            (new Extend\Model(User::class))
                ->hasMany('customSerializerRelation', Discussion::class, 'user_id'),
            (new Extend\ApiResource(UserResource::class))
                ->fields(fn () => [
                    Schema\Relationship\ToMany::make('customSerializerRelation')
                        ->type('discussions')
                        ->includable()
                ])
                ->endpoint(Show::class, function (Show $endpoint) {
                    return $endpoint->addDefaultInclude(['customSerializerRelation']);
                })
        );

        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])
        );

        $responseJson = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('customSerializerRelation', $responseJson['data']['relationships']);
        $this->assertCount(4, $responseJson['data']['relationships']['customSerializerRelation']['data']);
    }

    #[Test]
    public function custom_hasOne_relationship_exists_if_added()
    {
        $this->extend(
            (new Extend\Model(User::class))
                ->hasOne('customSerializerRelation', Discussion::class, 'user_id'),
            (new Extend\ApiResource(UserResource::class))
                ->fields(fn () => [
                    Schema\Relationship\ToOne::make('customSerializerRelation')
                        ->type('discussions')
                        ->includable()
                ])
                ->endpoint(Show::class, function (Show $endpoint) {
                    return $endpoint->addDefaultInclude(['customSerializerRelation']);
                })
        );

        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])
        );

        $responseJson = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('customSerializerRelation', $responseJson['data']['relationships']);
        $this->assertEquals('discussions', $responseJson['data']['relationships']['customSerializerRelation']['data']['type']);
    }

    #[Test]
    public function custom_relationship_is_inherited_to_child_classes()
    {
        $this->extend(
            (new Extend\Model(User::class))
                ->hasMany('anotherCustomRelation', Discussion::class, 'user_id'),
            (new Extend\ApiResource(AbstractDatabaseResource::class))
                ->fields(fn () => [
                    Schema\Relationship\ToMany::make('anotherCustomRelation')
                        ->type('discussions')
                        ->includable()
                ])
                ->endpoint(Show::class, function (Show $endpoint) {
                    return $endpoint->addDefaultInclude(['anotherCustomRelation']);
                })
        );

        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])
        );

        $responseJson = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('anotherCustomRelation', $responseJson['data']['relationships']);
        $this->assertCount(4, $responseJson['data']['relationships']['anotherCustomRelation']['data']);
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

class CustomAttributesInvokableClass
{
    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('customAttributeFromInvokable')
                ->get(fn () => true),
        ];
    }
}
