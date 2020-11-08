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

    protected function prepDb()
    {
        $this->prepareDatabase([
            'users' => [
                $this->adminUser(),
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
            'settings' => [
                ['key' => 'customPrefix.customSetting', 'value' => 'customValue']
            ]
        ]);
    }

    /**
     * @test
     */
    public function custom_attribute_doesnt_exist_by_default()
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
    public function custom_attribute_exists_if_added()
    {
        $this->extend(
            (new Extend\ApiSerializer(ForumSerializer::class))
                ->attributes(function () {
                    return [
                        'customAttribute' => true
                    ];
                })->attributes(CustomAttributesInvokableClass::class)
        );

        $this->app();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('customAttribute', $payload['data']['attributes']);
        $this->assertArrayHasKey('customAttributeFromInvokable', $payload['data']['attributes']);
    }

    /**
     * @test
     */
    public function custom_setting_exists_if_added()
    {
        $this->extend(
            (new Extend\ApiSerializer(ForumSerializer::class))
                ->setting('customPrefix.customSetting', 'default')
        );

        $this->prepDb();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('customPrefix.customSetting', $payload['data']['attributes']);
        $this->assertEquals('customValue', $payload['data']['attributes']['customPrefix.customSetting']);
    }

    /**
     * @test
     */
    public function custom_setting_falls_back_to_default_value()
    {
        $this->extend(
            (new Extend\ApiSerializer(ForumSerializer::class))
                ->setting('customPrefix.unavailableCustomSetting', 'default')
        );

        $this->prepDb();

        $response = $this->send(
            $this->request('GET', '/api', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('customPrefix.unavailableCustomSetting', $payload['data']['attributes']);
        $this->assertEquals('default', $payload['data']['attributes']['customPrefix.unavailableCustomSetting']);
    }

    /**
     * @test
     */
    public function custom_hasMany_relationship_exists_if_added()
    {
        $this->extend(
            (new Extend\Model(User::class))
                ->hasMany('customRelation', Discussion::class, 'user_id'),

            (new Extend\ApiSerializer(UserSerializer::class))
                ->hasManyRelationship('customRelation', DiscussionSerializer::class)
        );

        $this->prepDb();

        $request = $this->request('GET', '/api/users/2', [
            'authenticatedAs' => 1,
        ]);

        $serializer = $this->app()->getContainer()->make(UserSerializer::class);
        $serializer->setRequest($request);

        $relationship = $serializer->getRelationship(User::find(2), 'customRelation');

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
                ->hasOne('customRelation', Discussion::class, 'user_id'),

            (new Extend\ApiSerializer(UserSerializer::class))
                ->hasOneRelationship('customRelation', DiscussionSerializer::class)
        );

        $this->prepDb();

        $request = $this->request('GET', '/api/users/2', [
            'authenticatedAs' => 1,
        ]);

        $serializer = $this->app()->getContainer()->make(UserSerializer::class);
        $serializer->setRequest($request);

        $relationship = $serializer->getRelationship(User::find(2), 'customRelation');

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
                ->hasOne('customRelation', Discussion::class, 'user_id'),

            (new Extend\ApiSerializer(UserSerializer::class))
                ->relationship('customRelation', function (AbstractSerializer $serializer, $model) {
                    return $serializer->hasOne($model, DiscussionSerializer::class, 'customRelation');
                })
        );

        $this->prepDb();

        $request = $this->request('GET', '/api/users/2', [
            'authenticatedAs' => 1,
        ]);

        $serializer = $this->app()->getContainer()->make(UserSerializer::class);
        $serializer->setRequest($request);

        $relationship = $serializer->getRelationship(User::find(2), 'customRelation');

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
                ->hasOne('customRelation', Discussion::class, 'user_id'),

            (new Extend\ApiSerializer(UserSerializer::class))
                ->relationship('customRelation', CustomRelationshipInvokableClass::class)
        );

        $this->prepDb();

        $request = $this->request('GET', '/api/users/2', [
            'authenticatedAs' => 1,
        ]);

        $serializer = $this->app()->getContainer()->make(UserSerializer::class);
        $serializer->setRequest($request);

        $relationship = $serializer->getRelationship(User::find(2), 'customRelation');

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
                ->hasManyRelationship('anotherCustomRelation', DiscussionSerializer::class)
        );

        $this->prepDb();

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
                ->hasOneRelationship('postCustomRelation', PostSerializer::class),

            (new Extend\ApiSerializer(UserSerializer::class))
                ->relationship('postCustomRelation', function (AbstractSerializer $serializer, $model) {
                    return $serializer->hasOne($model, DiscussionSerializer::class, 'discussionCustomRelation');
                })
        );

        $this->prepDb();

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

class CustomRelationshipInvokableClass
{
    public function __invoke(AbstractSerializer $serializer, $model)
    {
        return $serializer->hasOne($model, DiscussionSerializer::class, 'customRelation');
    }
}
