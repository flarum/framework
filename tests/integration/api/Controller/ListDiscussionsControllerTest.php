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
use Flarum\Api\Controller\ListDiscussionsController;
use Flarum\User\User;

class ListDiscussionsControllerTest extends ApiControllerTestCase
{
    protected $controller = ListDiscussionsController::class;

    public function setUp()
    {
        parent::setUp();

        $this->prepareDatabase([
            'discussions' => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>'],
            ],
            'users' => [
                $this->normalUser(),
            ],
            'groups' => [
                $this->memberGroup(),
                $this->guestGroup(),
            ],
            'group_permission' => [
                ['permission' => 'viewDiscussions', 'group_id' => 2],
            ]
        ]);
    }

    /**
     * @test
     */
    public function shows_index_for_guest()
    {
        $response = $this->callWith();

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(1, count($data['data']));
    }

    /**
     * @test
     */
    public function can_search_for_author()
    {
        $user = User::find(2);

        $response = $this->callWith([], [
            'filter' => [
                'q' => 'author:'.$user->username.' foo'
            ],
            'include' => 'mostRelevantPost'
        ]);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function can_search_for_word_in_post()
    {
        $this->database()->table('posts')->insert([
            ['id' => 2, 'discussion_id' => 2, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>not in text</p></t>'],
            ['id' => 3, 'discussion_id' => 3, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>lightsail in text</p></t>'],
        ]);

        $this->database()->table('discussions')->insert([
            ['id' => 2, 'title' => 'lightsail in title', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 2, 'comment_count' => 1],
            ['id' => 3, 'title' => 'not in title', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 3, 'comment_count' => 1],
        ]);

        $response = $this->callWith([], [
            'filter' => ['q' => 'lightsail'],
            'include' => 'mostRelevantPost'
        ]);
        $data = json_decode($response->getBody()->getContents(), true);
        $ids = array_map(function ($row) {
            return $row['id'];
        }, $data['data']);

        // Order-independent comparison
        $this->assertEquals(['3'], $ids, 'IDs do not match', 0.0, 10, true);
    }

    /**
     * @test
     */
    public function ignores_non_word_characters_when_searching()
    {
        $this->database()->table('posts')->insert([
            ['id' => 2, 'discussion_id' => 2, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>not in text</p></t>'],
            ['id' => 3, 'discussion_id' => 3, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>lightsail in text</p></t>'],
        ]);

        $this->database()->table('discussions')->insert([
            ['id' => 2, 'title' => 'lightsail in title', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 2, 'comment_count' => 1],
            ['id' => 3, 'title' => 'not in title', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 3, 'comment_count' => 1],
        ]);

        $response = $this->callWith([], [
            'filter' => ['q' => 'lightsail+'],
            'include' => 'mostRelevantPost'
        ]);
        $data = json_decode($response->getBody()->getContents(), true);
        $ids = array_map(function ($row) {
            return $row['id'];
        }, $data['data']);

        // Order-independent comparison
        $this->assertEquals(['3'], $ids, 'IDs do not match', 0.0, 10, true);
    }

    /**
     * @test
     */
    public function search_for_special_characters_gives_empty_result()
    {
        $response = $this->callWith([], [
            'filter' => ['q' => '*'],
            'include' => 'mostRelevantPost'
        ]);
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals([], $data['data']);

        $response = $this->callWith([], [
            'filter' => ['q' => '@'],
            'include' => 'mostRelevantPost'
        ]);
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals([], $data['data']);
    }
}
