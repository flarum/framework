<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes\Tests\performance;

use Flarum\Likes\Tests\integration\api\UserLikeCountTest;
use PHPUnit\Framework\Attributes\Test;

class LikeCountBenchmarkTest extends UserLikeCountTest
{
    #[Test]
    public function benchmark_deleting_a_popular_post(): void
    {
        $db = $this->database();
        $users = [];

        for ($id = 10; $id < 3010; $id++) {
            $users[] = [
                'id' => $id,
                'username' => 'bench'.$id,
                'email' => 'bench'.$id.'@example.invalid',
                'like_count' => 1000,
                'password' => '', // No usable login is needed for benchmark fixtures.
            ];
        }

        foreach (array_chunk($users, 500) as $chunk) {
            $db->table('users')->insert($chunk);
        }

        $posts = [];

        for ($id = 10; $id < 1010; $id++) {
            $posts[] = [
                'id' => $id,
                'discussion_id' => 1,
                'number' => $id,
                'type' => 'comment',
                'user_id' => 3,
                'created_at' => '2026-01-01 00:00:00',
                'content' => '<t><p>history</p></t>',
            ];
        }

        foreach (array_chunk($posts, 500) as $chunk) {
            $db->table('posts')->insert($chunk);
        }

        $rows = [];

        for ($user = 10; $user < 3010; $user++) {
            for ($post = 10; $post < 1010; $post++) {
                $rows[] = ['user_id' => $user, 'post_id' => $post, 'is_counted' => true];

                if (count($rows) === 5000) {
                    $db->table('post_likes')->insert($rows);
                    $rows = [];
                }
            }
        }

        foreach ([0, 1000, 3000] as $n) {
            $times = [];

            for ($trial = 0; $trial < 3; $trial++) {
                $id = 2000 + $n + $trial;
                $db->table('posts')->insert([
                    'id' => $id,
                    'discussion_id' => 1,
                    'number' => $id,
                    'type' => 'comment',
                    'user_id' => 3,
                    'created_at' => '2026-01-01 00:00:00',
                    'content' => '<t><p>target</p></t>',
                ]);
                $rows = [];

                for ($user = 10; $user < 10 + $n; $user++) {
                    $rows[] = ['post_id' => $id, 'user_id' => $user, 'is_counted' => true];
                }

                foreach (array_chunk($rows, 1000) as $chunk) {
                    $db->table('post_likes')->insert($chunk);
                }

                $db->table('users')->whereBetween('id', [10, 9 + $n])->increment('like_count');
                $start = hrtime(true);
                $response = $this->send($this->request('DELETE', '/api/posts/'.$id, ['authenticatedAs' => 1]));
                $times[] = (hrtime(true) - $start) / 1e6;

                $this->assertEquals(204, $response->getStatusCode());
                $this->assertSame(1000, $this->likeCountOf(10));
            }

            sort($times);

            echo 'BENCH '.json_encode([
                'driver' => $db->getDriverName(),
                'likers' => $n,
                'historyPerLiker' => 1000,
                'medianMs' => round($times[1], 2),
                'trialsMs' => $times,
            ]).PHP_EOL;
        }
    }
}
