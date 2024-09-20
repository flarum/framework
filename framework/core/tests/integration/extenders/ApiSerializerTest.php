<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Carbon\Carbon;
use Flarum\Api\Endpoint\Show;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Resource\ForumResource;
use Flarum\Api\Resource\UserResource;
use Flarum\Api\Schema;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

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
            User::class => [
                $this->normalUser()
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Custom Discussion Title', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 0, 'comment_count' => 1, 'is_private' => 0],
                ['id' => 2, 'title' => 'Custom Discussion Title', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 0, 'comment_count' => 1, 'is_private' => 0],
                ['id' => 3, 'title' => 'Custom Discussion Title', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 0, 'comment_count' => 1, 'is_private' => 0],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 3, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'discussionRenamed', 'content' => '<t><p>can i haz relationz?</p></t>'],
            ],
        ]);
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
    public function custom_attributes_can_be_overriden()
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
        $this->assertCount(3, $responseJson['data']['relationships']['customSerializerRelation']['data']);
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
        $this->assertCount(3, $responseJson['data']['relationships']['anotherCustomRelation']['data']);
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
