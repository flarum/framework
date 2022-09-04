<?php

namespace Flarum\Statistics\tests\integration\api;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

class CanRequestLifetimeStatisticsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected Carbon $nowTime;

    protected function setUp(): void
    {
        parent::setUp();

        $this->nowTime = Carbon::now();

        $this->extension('flarum-statistics');

        $this->prepareDatabase($this->getDatabaseData());
    }

    protected function getDatabaseData(): array
    {
        return [
            'users' => [
                ['id' => 1, 'username' => 'Muralf', 'email' => 'muralf@machine.local', 'is_email_confirmed' => 1],
                ['id' => 2, 'username' => 'normal', 'email' => 'normal@machine.local', 'is_email_confirmed' => 1, 'joined_at' => $this->nowTime->subDays(1)],
            ],
            'discussions' => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => $this->nowTime, 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => __CLASS__, 'created_at' => $this->nowTime->subDays(1), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
                ['id' => 3, 'title' => __CLASS__, 'created_at' => $this->nowTime->subDays(1), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
                ['id' => 4, 'title' => __CLASS__, 'created_at' => $this->nowTime->subDays(2), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'number' => 1],
                ['id' => 2, 'discussion_id' => 2, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'number' => 1],
                ['id' => 3, 'discussion_id' => 3, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'number' => 1],
                ['id' => 4, 'discussion_id' => 4, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'number' => 1],
                ['id' => 5, 'discussion_id' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'number' => 2],
            ],
        ];
    }

    /** 
     * @test
     */
    public function can_request_lifetime_stats()
    {
        $response = $this->send(
            $this->request('GET', '/api/statistics', ['authenticatedAs' => 1])->withQueryParams([
                'period' => 'lifetime',
            ])
        );

        $body = json_decode($response->getBody()->getContents(), true);

        $db = $this->getDatabaseData();

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEqualsCanonicalizing(
            [
                'users' => count($db['users']),
                'discussions' => count($db['discussions']),
                'posts' => count($db['posts']),
            ],
            $body
        );
    }
}
