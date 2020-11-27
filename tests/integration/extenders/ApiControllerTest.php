<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Carbon\Carbon;
use Flarum\Api\Controller\ListDiscussionsController;
use Flarum\Api\Controller\ShowDiscussionController;
use Flarum\Api\Controller\ShowPostController;
use Flarum\Api\Controller\ShowUserController;
use Flarum\Api\Serializer\DiscussionSerializer;
use Flarum\Api\Serializer\PostSerializer;
use Flarum\Api\Serializer\UserSerializer;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;

class ApiControllerTest extends TestCase
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
                ['id' => 2, 'title' => 'Custom Discussion Title', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 3, 'first_post_id' => 0, 'comment_count' => 1, 'is_private' => 0],
                ['id' => 3, 'title' => 'Custom Discussion Title', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 0, 'comment_count' => 1, 'is_private' => 0],
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

        $this->prepDb();

        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

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

        $this->prepDb();

        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

        $this->assertEquals(CustomPrepareDataSerializationInvokableClass::class, $payload['data']['attributes']['title']);
    }

    /**
     * @test
     */
    public function custom_serializer_doesnt_work_by_default()
    {
        $this->prepDb();

        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

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

        $this->prepDb();

        $response = $this->send(
            $this->request('GET', '/api/discussions/1', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

        $this->assertArrayHasKey('customSerializer', $payload['data']['attributes']);
    }

    /**
     * @test
     */
    public function custom_serializer_works_if_set_with_invokable_class()
    {
        $this->extend(
            (new Extend\ApiController(ShowPostController::class))
                ->setSerializer(CustomPostSerializer::class, CustomInvokableClass::class)
        );

        $this->prepDb();
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

        $payload = json_decode($response->getBody(), true);

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

        $this->prepDb();

        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

        $this->assertArrayNotHasKey('customSerializer', $payload['data']['attributes']);
    }

    /**
     * @test
     */
    public function custom_limit_doesnt_work_by_default()
    {
        $this->prepDb();

        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

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

        $this->prepDb();

        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

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

        $this->prepDb();

        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'page' => ['limit' => '5'],
            ])
        );

        $payload = json_decode($response->getBody(), true);

        $this->assertCount(1, $payload['data']);
    }

    /**
     * @test
     */
    public function custom_sort_field_doesnt_exist_by_default()
    {
        $this->prepDb();

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

        $this->prepDb();

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

        $this->prepDb();

        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'sort' => 'userId',
            ])
        );

        $payload = json_decode($response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(3, $payload['data'][0]['id']);
    }

    /**
     * @test
     */
    public function custom_sort_field_exists_by_default()
    {
        $this->prepDb();

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

        $this->prepDb();

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

        $this->prepDb();

        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 1,
            ])
        );

        $payload = json_decode($response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(2, $payload['data'][0]['id']);
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

class CustomInvokableClass
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
