<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\Controller;

use Carbon\Carbon;
use Flarum\Api\Controller\CreatePostController;
use Flarum\User\User;
use Illuminate\Support\Arr;

class CreatePostControllerTest extends ApiControllerTestCase
{
    protected $controller = CreatePostController::class;

    protected $data = [
        'content' => 'reply with predetermined content for automated testing - too-obscure'
    ];

    public function setUp()
    {
        parent::setUp();

        $this->prepareDatabase([
            'discussions' => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2],
            ],
            'posts' => [],
            'users' => [
                $this->normalUser(),
            ],
            'groups' => [
                $this->memberGroup(),
            ],
            'group_user' => [
                ['user_id' => 2, 'group_id' => 3],
            ],
            'group_permission' => [
                ['permission' => 'viewDiscussions', 'group_id' => 3],
            ]
        ]);
    }

    /**
     * @test
     */
    public function can_create_reply()
    {
        $this->actor = User::find(2);

        $body = [];
        Arr::set($body, 'data.attributes', $this->data);
        Arr::set($body, 'data.relationships.discussion.data.id', 1);

        $response = $this->callWith($body);

        $this->assertEquals(201, $response->getStatusCode());
    }
}
