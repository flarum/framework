<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Carbon\Carbon;
use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\Api\Serializer\DiscussionSerializer;
use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Api\Serializer\PostSerializer;
use Flarum\Api\Serializer\UserSerializer;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Post\Post;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;
use Flarum\User\User;

class ApiSerializerTest extends TestCase
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
                ['id' => 2, 'title' => 'Custom Discussion Title', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 0, 'comment_count' => 1, 'is_private' => 0],
                ['id' => 3, 'title' => 'Custom Discussion Title', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 0, 'comment_count' => 1, 'is_private' => 0],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 3, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'discussionRenamed', 'content' => '<t><p>can i haz relationz?</p></t>'],
            ],
        ]);
    }

    /**
     * @test
     */
    public function custom_attributes_dont_exist_by_default()
    {
        $this->app();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

        $this->assertArrayNotHasKey('customAttribute', $payload['data']['attributes']);
    }

    /**
     * @test
     */
    public function custom_attributes_exist_if_added()
    {
        $this->extend(
            (new Extend\ApiSerializer(ForumSerializer::class))
                ->mutate(function () {
                    return [
                        'customAttribute' => true
                    ];
                })
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('customAttribute', $payload['data']['attributes']);
    }

    /**
     * @test
     */
    public function custom_attributes_with_invokable_exist_if_added()
    {
        $this->extend(
            (new Extend\ApiSerializer(ForumSerializer::class))
                ->mutate(CustomAttributesInvokableClass::class)
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('customAttributeFromInvokable', $payload['data']['attributes']);
    }

    /**
     * @test
     */
    public function custom_attributes_exist_if_added_to_parent_class()
    {
        $this->extend(
            (new Extend\ApiSerializer(BasicUserSerializer::class))
                ->mutate(function () {
                    return [
                        'customAttribute' => true
                    ];
                })
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('customAttribute', $payload['data']['attributes']);
    }

    /**
     * @test
     */
    public function custom_attributes_prioritize_child_classes()
    {
        $this->extend(
            (new Extend\ApiSerializer(BasicUserSerializer::class))
                ->mutate(function () {
                    return [
                        'customAttribute' => 'initialValue'
                    ];
                }),
            (new Extend\ApiSerializer(UserSerializer::class))
                ->mutate(function () {
                    return [
                        'customAttribute' => 'newValue'
                    ];
                })
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('customAttribute', $payload['data']['attributes']);
        $this->assertEquals('newValue', $payload['data']['attributes']['customAttribute']);
    }

    /**
     * @test
     */
    public function custom_single_attribute_exists_if_added()
    {
        $this->extend(
            (new Extend\ApiSerializer(ForumSerializer::class))
                ->attribute('customSingleAttribute', function () {
                    return true;
                })->attribute('customSingleAttribute_0', function () {
                    return 0;
                })
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('customSingleAttribute', $payload['data']['attributes']);
        $this->assertArrayHasKey('customSingleAttribute_0', $payload['data']['attributes']);
        $this->assertEquals(0, $payload['data']['attributes']['customSingleAttribute_0']);
    }

    /**
     * @test
     */
    public function custom_single_attribute_with_invokable_exists_if_added()
    {
        $this->extend(
            (new Extend\ApiSerializer(ForumSerializer::class))
                ->attribute('customSingleAttribute_1', CustomSingleAttributeInvokableClass::class)
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('customSingleAttribute_1', $payload['data']['attributes']);
    }

    /**
     * @test
     */
    public function custom_single_attribute_exists_if_added_to_parent_class()
    {
        $this->extend(
            (new Extend\ApiSerializer(BasicUserSerializer::class))
                ->attribute('customSingleAttribute_2', function () {
                    return true;
                })
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('customSingleAttribute_2', $payload['data']['attributes']);
    }

    /**
     * @test
     */
    public function custom_single_attribute_prioritizes_child_classes()
    {
        $this->extend(
            (new Extend\ApiSerializer(BasicUserSerializer::class))
                ->attribute('customSingleAttribute_3', function () {
                    return 'initialValue';
                }),
            (new Extend\ApiSerializer(UserSerializer::class))
                ->attribute('customSingleAttribute_3', function () {
                    return 'newValue';
                })
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('customSingleAttribute_3', $payload['data']['attributes']);
        $this->assertEquals('newValue', $payload['data']['attributes']['customSingleAttribute_3']);
    }

    /**
     * @test
     */
    public function custom_attributes_can_be_overriden()
    {
        $this->extend(
            (new Extend\ApiSerializer(BasicUserSerializer::class))
                ->attribute('someCustomAttribute', function () {
                    return 'newValue';
                })->mutate(function () {
                    return [
                        'someCustomAttribute' => 'initialValue',
                        'someOtherCustomAttribute' => 'initialValue',
                    ];
                })->attribute('someOtherCustomAttribute', function () {
                    return 'newValue';
                })
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('someCustomAttribute', $payload['data']['attributes']);
        $this->assertEquals('newValue', $payload['data']['attributes']['someCustomAttribute']);
        $this->assertArrayHasKey('someOtherCustomAttribute', $payload['data']['attributes']);
        $this->assertEquals('newValue', $payload['data']['attributes']['someOtherCustomAttribute']);
    }

    /**
     * @test
     */
    public function custom_hasMany_relationship_exists_if_added()
    {
        $this->extend(
            (new Extend\Model(User::class))
                ->hasMany('customSerializerRelation', Discussion::class, 'user_id'),
            (new Extend\ApiSerializer(UserSerializer::class))
                ->hasMany('customSerializerRelation', DiscussionSerializer::class)
        );

        $request = $this->request('GET', '/api/users/2', [
            'authenticatedAs' => 1,
        ]);

        $serializer = $this->app()->getContainer()->make(UserSerializer::class);
        $serializer->setRequest($request);

        $relationship = $serializer->getRelationship(User::find(2), 'customSerializerRelation');

        $this->assertNotEmpty($relationship);
        $this->assertCount(3, $relationship->toArray()['data']);
    }

    /**
     * @test
     */
    public function custom_hasOne_relationship_exists_if_added()
    {
        $this->extend(
            (new Extend\Model(User::class))
                ->hasOne('customSerializerRelation', Discussion::class, 'user_id'),
            (new Extend\ApiSerializer(UserSerializer::class))
                ->hasOne('customSerializerRelation', DiscussionSerializer::class)
        );

        $request = $this->request('GET', '/api/users/2', [
            'authenticatedAs' => 1,
        ]);

        $serializer = $this->app()->getContainer()->make(UserSerializer::class);
        $serializer->setRequest($request);

        $relationship = $serializer->getRelationship(User::find(2), 'customSerializerRelation');

        $this->assertNotEmpty($relationship);
        $this->assertEquals('discussions', $relationship->toArray()['data']['type']);
    }

    /**
     * @test
     */
    public function custom_relationship_exists_if_added()
    {
        $this->extend(
            (new Extend\Model(User::class))
                ->hasOne('customSerializerRelation', Discussion::class, 'user_id'),
            (new Extend\ApiSerializer(UserSerializer::class))
                ->relationship('customSerializerRelation', function (AbstractSerializer $serializer, $model) {
                    return $serializer->hasOne($model, DiscussionSerializer::class, 'customSerializerRelation');
                })
        );

        $request = $this->request('GET', '/api/users/2', [
            'authenticatedAs' => 1,
        ]);

        $serializer = $this->app()->getContainer()->make(UserSerializer::class);
        $serializer->setRequest($request);

        $relationship = $serializer->getRelationship(User::find(2), 'customSerializerRelation');

        $this->assertNotEmpty($relationship);
        $this->assertEquals('discussions', $relationship->toArray()['data']['type']);
    }

    /**
     * @test
     */
    public function custom_relationship_with_invokable_exists_if_added()
    {
        $this->extend(
            (new Extend\Model(User::class))
                ->hasOne('customSerializerRelation', Discussion::class, 'user_id'),
            (new Extend\ApiSerializer(UserSerializer::class))
                ->relationship('customSerializerRelation', CustomRelationshipInvokableClass::class)
        );

        $request = $this->request('GET', '/api/users/2', [
            'authenticatedAs' => 1,
        ]);

        $serializer = $this->app()->getContainer()->make(UserSerializer::class);
        $serializer->setRequest($request);

        $relationship = $serializer->getRelationship(User::find(2), 'customSerializerRelation');

        $this->assertNotEmpty($relationship);
        $this->assertEquals('discussions', $relationship->toArray()['data']['type']);
    }

    /**
     * @test
     */
    public function custom_relationship_is_inherited_to_child_classes()
    {
        $this->extend(
            (new Extend\Model(User::class))
                ->hasMany('anotherCustomRelation', Discussion::class, 'user_id'),
            (new Extend\ApiSerializer(BasicUserSerializer::class))
                ->hasMany('anotherCustomRelation', DiscussionSerializer::class)
        );

        $request = $this->request('GET', '/api/users/2', [
            'authenticatedAs' => 1,
        ]);

        $serializer = $this->app()->getContainer()->make(UserSerializer::class);
        $serializer->setRequest($request);

        $relationship = $serializer->getRelationship(User::find(2), 'anotherCustomRelation');

        $this->assertNotEmpty($relationship);
        $this->assertCount(3, $relationship->toArray()['data']);
    }

    /**
     * @test
     */
    public function custom_relationship_prioritizes_child_classes()
    {
        $this->extend(
            (new Extend\Model(User::class))
                ->hasOne('postCustomRelation', Post::class, 'user_id'),
            (new Extend\Model(User::class))
                ->hasOne('discussionCustomRelation', Discussion::class, 'user_id'),
            (new Extend\ApiSerializer(BasicUserSerializer::class))
                ->hasOne('postCustomRelation', PostSerializer::class),
            (new Extend\ApiSerializer(UserSerializer::class))
                ->relationship('postCustomRelation', function (AbstractSerializer $serializer, $model) {
                    return $serializer->hasOne($model, DiscussionSerializer::class, 'discussionCustomRelation');
                })
        );

        $request = $this->request('GET', '/api/users/2', [
            'authenticatedAs' => 1,
        ]);

        $serializer = $this->app()->getContainer()->make(UserSerializer::class);
        $serializer->setRequest($request);

        $relationship = $serializer->getRelationship(User::find(2), 'postCustomRelation');

        $this->assertNotEmpty($relationship);
        $this->assertEquals('discussions', $relationship->toArray()['data']['type']);
    }
}

class CustomAttributesInvokableClass
{
    public function __invoke()
    {
        return [
            'customAttributeFromInvokable' => true
        ];
    }
}

class CustomSingleAttributeInvokableClass
{
    public function __invoke()
    {
        return true;
    }
}

class CustomRelationshipInvokableClass
{
    public function __invoke(AbstractSerializer $serializer, $model)
    {
        return $serializer->hasOne($model, DiscussionSerializer::class, 'customSerializerRelation');
    }
}
