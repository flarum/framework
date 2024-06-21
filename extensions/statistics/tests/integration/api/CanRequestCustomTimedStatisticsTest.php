<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Statistics\tests\integration\api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class CanRequestCustomTimedStatisticsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @var Carbon
     */
    protected $nowTime;

    protected function setUp(): void
    {
        parent::setUp();

        $this->nowTime = Carbon::now()->subDays(10);

        $this->extension('flarum-statistics');

        $this->prepareDatabase($this->getDatabaseData());
    }

    protected function getDatabaseData(): array
    {
        return [
            User::class => [
                ['id' => 1, 'username' => 'Muralf', 'email' => 'muralf@machine.local', 'is_email_confirmed' => 1, 'joined_at' => $this->nowTime->copy()],
                ['id' => 2, 'username' => 'normal', 'email' => 'normal@machine.local', 'is_email_confirmed' => 1, 'joined_at' => $this->nowTime->copy()->subDays(1)],
                ['id' => 3, 'username' => 'normal2', 'email' => 'normal2@machine.local', 'is_email_confirmed' => 1, 'joined_at' => $this->nowTime->copy()->subDays(2)],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => $this->nowTime->copy(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => __CLASS__, 'created_at' => $this->nowTime->copy()->subDays(1), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
                ['id' => 3, 'title' => __CLASS__, 'created_at' => $this->nowTime->copy()->subDays(1), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
                ['id' => 4, 'title' => __CLASS__, 'created_at' => $this->nowTime->copy()->subDays(2), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'number' => 1, 'created_at' => $this->nowTime->copy()],
                ['id' => 2, 'discussion_id' => 2, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'number' => 1, 'created_at' => $this->nowTime->copy()->subDays(1)],
                ['id' => 3, 'discussion_id' => 3, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'number' => 1, 'created_at' => $this->nowTime->copy()->subDays(1)],
                ['id' => 4, 'discussion_id' => 4, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'number' => 1, 'created_at' => $this->nowTime->copy()->subDays(2)],
                ['id' => 5, 'discussion_id' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_private' => 0, 'number' => 2, 'created_at' => $this->nowTime->copy()],
            ],
        ];
    }

    /**
     * @test
     */
    public function can_request_timed_stats()
    {
        $time = $this->nowTime->copy();

        $start = $time->copy()->subDays(1)->startOfDay()->getTimestamp();
        $end = $time->copy()->endOfDay()->getTimestamp();

        $timeStart = $time->copy()->startOfDay();

        $models = [
            'users' => [
                $timeStart->copy()->getTimestamp() => 1,
                $timeStart->copy()->subDays(1)->getTimestamp() => 1,
                $timeStart->copy()->subDays(2)->getTimestamp() => 1,
            ], 'discussions' => [
                $timeStart->copy()->getTimestamp() => 1,
                $timeStart->copy()->subDays(1)->getTimestamp() => 2,
                $timeStart->copy()->subDays(2)->getTimestamp() => 1,
            ], 'posts' => [
                $timeStart->copy()->getTimestamp() => 2,
                $timeStart->copy()->subDays(1)->getTimestamp() => 2,
                $timeStart->copy()->subDays(2)->getTimestamp() => 1,
            ]
        ];

        foreach ($models as $model => $data) {
            $response = $this->send(
                $this->request('GET', '/api/statistics', ['authenticatedAs' => 1])->withQueryParams([
                    'model' => $model,
                    'period' => 'custom',
                    'dateRange' => [
                        'start' => $start,
                        'end' => $end,
                    ],
                ])
            );

            $body = json_decode($response->getBody()->getContents(), true);

            $this->assertEquals(200, $response->getStatusCode(), $body['errors'][0]['detail'] ?? '');

            $this->assertEquals(
                $data,
                $body,
            );
        }
    }
}
