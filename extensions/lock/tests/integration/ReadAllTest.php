<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Lock\Tests\integration;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class ReadAllTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    #[Test]
    public function an_empty_filter_marks_only_matching_discussions_read()
    {
        $this->extension('flarum-lock');
        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => [
                ['id' => 1, 'title' => 'Matching', 'user_id' => 2, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'comment_count' => 1, 'last_post_number' => 5, 'is_locked' => true],
                ['id' => 2, 'title' => 'Unrelated', 'user_id' => 2, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'comment_count' => 1, 'last_post_number' => 7, 'is_locked' => false],
            ],
        ]);

        $response = $this->send(
            $this->request('POST', '/api/discussions/read', ['authenticatedAs' => 2])
                ->withQueryParams(['filter' => ['locked' => '']])
        );

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals(5, $this->database()->table('discussion_user')->where(['user_id' => 2, 'discussion_id' => 1])->value('last_read_post_number'));
        $this->assertNull($this->database()->table('discussion_user')->where(['user_id' => 2, 'discussion_id' => 2])->first());
        $this->assertNull($this->database()->table('users')->find(2)->marked_all_as_read_at);
    }
}
