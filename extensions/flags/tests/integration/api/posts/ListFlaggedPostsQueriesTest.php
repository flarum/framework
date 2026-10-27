<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Tests\integration\api\posts;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Flags\Flag;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Posts are listed with their flags and who raised them. Those must be loaded
 * for the whole page, not flag by flag: the integration harness fails a
 * request whose queries grow with the flags listed.
 */
class ListFlaggedPostsQueriesTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-flags');

        $users = [];
        $posts = [];
        $flags = [];

        foreach (range(2, 11) as $id) {
            $users[] = ['id' => $id, 'username' => "flagger$id", 'email' => "flagger$id@machine.local", 'is_email_confirmed' => 1];
        }

        foreach (range(1, 10) as $id) {
            // Each by someone else, and flagged by someone else again.
            $posts[] = ['id' => $id, 'number' => $id, 'discussion_id' => 1, 'user_id' => $id + 1, 'created_at' => Carbon::now()->subMinutes($id), 'type' => 'comment', 'content' => "<t><p>Post $id</p></t>"];
            $flags[] = ['id' => $id, 'post_id' => $id, 'user_id' => ($id % 10) + 2, 'type' => 'user', 'created_at' => Carbon::now()];
        }

        $this->prepareDatabase([
            User::class => $users,
            Discussion::class => [
                ['id' => 1, 'title' => 'Flagged', 'user_id' => 1, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'first_post_id' => 1, 'comment_count' => 10, 'last_post_number' => 10],
            ],
            Post::class => $posts,
            Flag::class => $flags,
        ]);
    }

    #[Test]
    public function flags_and_who_raised_them_are_loaded_for_the_whole_page(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 1])->withQueryParams(['filter' => ['discussion' => '1']])
        );

        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertCount(10, collect($body['included'])->where('type', 'flags'));
        $this->assertCount(10, collect($body['included'])->where('type', 'users'));
    }
}
