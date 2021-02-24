<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\posts;

use Carbon\Carbon;
use Flarum\Event\ConfigurePostsQuery;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;
use Illuminate\Support\Arr;

class ListTests extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'discussions' => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 2],
                ['id' => 2, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 2],
            ],
            'posts' => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>something</p></t>'],
                ['id' => 2, 'number' => 1, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>something</p></t>'],
                ['id' => 3, 'number' => 2, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>something</p></t>'],
                ['id' => 4, 'number' => 2, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>something</p></t>'],
                ['id' => 5, 'number' => 3, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'discussionRenamed', 'content' => '<t><p>something</p></t>'],
            ],
            'users' => [
                $this->normalUser(),
            ],
        ]);
    }

    private function forbidGuestsFromSeeingForum()
    {
        $this->database()->table('group_permission')->where('permission', 'viewDiscussions')->where('group_id', 2)->delete();
    }

    /**
     * @test
     */
    public function guests_cant_see_anything_if_not_allowed()
    {
        $this->forbidGuestsFromSeeingForum();

        $response = $this->send(
            $this->request('GET', '/api/posts')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals([], $data['data']);
    }

    /**
     * @test
     */
    public function authorized_users_can_see_posts()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(5, count($data['data']));
    }

    /**
     * @test
     */
    public function author_filter_works()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 1])
                ->withQueryParams([
                    'filter' => ['author' => 'admin'],
                ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(['1', '2'], Arr::pluck($data['data'], 'id'));
    }

    /**
     * @test
     */
    public function author_filter_works_with_multiple_values()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 1])
                ->withQueryParams([
                    'filter' => ['author' => 'admin,normal'],
                ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(['1', '2', '3', '4', '5'], Arr::pluck($data['data'], 'id'));
    }

    /**
     * @test
     */
    public function discussion_filter_works()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 1])
            ->withQueryParams([
                'filter' => ['discussion' => '1'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(['1', '3'], Arr::pluck($data['data'], 'id'));
    }

    /**
     * @test
     */
    public function type_filter_works()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 1])
                ->withQueryParams([
                    'filter' => ['type' => 'discussionRenamed'],
                ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(['5'], Arr::pluck($data['data'], 'id'));
    }

    /**
     * @test
     */
    public function number_filter_works()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 1])
                ->withQueryParams([
                    'filter' => ['number' => '2'],
                ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(['3', '4'], Arr::pluck($data['data'], 'id'));
    }

    /**
     * @test
     */
    public function id_filter_works()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 1])
                ->withQueryParams([
                    'filter' => ['id' => '4'],
                ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(['4'], Arr::pluck($data['data'], 'id'));
    }

    /**
     * @test
     */
    public function id_filter_works_with_multiple_ids()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 1])
                ->withQueryParams([
                    'filter' => ['id' => '1,3,5'],
                ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(['1', '3', '5'], Arr::pluck($data['data'], 'id'));
    }

    /**
     * @deprecated beta 16, remove beta 17
     * @test
     */
    public function deprecated_configure_posts_query_extension_still_works()
    {
        $this->app()->getContainer()->make('events')->listen(ConfigurePostsQuery::class, function (ConfigurePostsQuery $event) {
            $event->query->where('id', '1');
        });

        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(['1'], Arr::pluck($data['data'], 'id'));
    }
}
