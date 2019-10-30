<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\Controller;

use Carbon\Carbon;
use Flarum\Api\Controller\DeleteDiscussionController;
use Flarum\User\User;

class DeleteDiscussionControllerTest extends ApiControllerTestCase
{
    protected $controller = DeleteDiscussionController::class;

    public function setUp()
    {
        parent::setUp();

        $this->prepareDatabase([
            'discussions' => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>'],
            ],
            'users' => [
                $this->adminUser(),
                $this->normalUser(),
            ],
            'groups' => [
                $this->adminGroup(),
            ],
            'group_user' => [
                ['user_id' => 1, 'group_id' => 1],
            ],
        ]);
    }

    /**
     * @test
     */
    public function admin_can_delete()
    {
        $this->actor = User::find(1);

        $response = $this->callWith([], ['id' => 1]);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function deleting_discussions_deletes_their_posts()
    {
        $this->actor = User::find(1);

        $this->callWith([], ['id' => 1]);

        $this->assertNull($this->database()->table('posts')->find(1), 'Post exists in the DB');
    }
}
