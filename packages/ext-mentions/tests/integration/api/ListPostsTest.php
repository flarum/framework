<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\discussions;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Arr;

class ListTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mentions');

        $this->prepareDatabase([
            'discussions' => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>text</p></t>'],
                ['id' => 2, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>text</p></t>'],
                ['id' => 3, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>text</p></t>'],
                ['id' => 4, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>text</p></t>'],
            ],
            'post_mentions_user' => [
                ['post_id' => 2, 'mentions_user_id' => 1],
                ['post_id' => 3, 'mentions_user_id' => 1],
                ['post_id' => 4, 'mentions_user_id' => 2]
            ],
            'users' => [
                $this->normalUser(),
            ]
        ]);
    }

    /**
     * @test
     */
    public function mentioned_filter_works()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts')
                ->withQueryParams([
                    'filter' => ['mentioned' => 1],
                ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $ids = Arr::pluck($data, 'id');
        $this->assertEqualsCanonicalizing(['2', '3'], $ids, 'IDs do not match');
    }

    /**
     * @test
     */
    public function mentioned_filter_works_negated()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts')
            ->withQueryParams([
                'filter' => ['-mentioned' => 1],
            ])
        );

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        // Order-independent comparison
        $ids = Arr::pluck($data, 'id');
        $this->assertEqualsCanonicalizing(['4'], $ids, 'IDs do not match');
    }
}
