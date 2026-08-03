<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Tests\integration\frontend;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Str;

class DiscussionPageMentionsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mentions');

        $posts = [];

        for ($i = 1; $i <= 25; $i++) {
            $posts[] = [
                'id' => $i,
                'number' => $i,
                'discussion_id' => 1,
                'created_at' => Carbon::parse('2024-01-01 00:00:00')->addMinutes($i),
                'user_id' => 2,
                'type' => 'comment',
                'content' => '<t><p>POST-MARKER-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT).'</p></t>',
            ];
        }

        $this->prepareDatabase([
            'discussions' => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 1, 'last_post_id' => 25, 'comment_count' => 25, 'is_private' => 0],
            ],
            'posts' => $posts,
            // Post 21 sits on page 2 and quotes post 2, which sits on page 1.
            'post_mentions_post' => [
                ['post_id' => 21, 'mentions_post_id' => 2],
            ],
            'users' => [
                $this->normalUser(),
            ],
        ]);
    }

    private function html(string $path): string
    {
        $body = $this->send($this->request('GET', $path))->getBody()->getContents();

        return Str::before($body, '<script id="flarum-json-payload"');
    }

    private function marker(int $number): string
    {
        return 'POST-MARKER-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT);
    }

    /**
     * This extension asks the discussion endpoint to include, for every post on
     * the page, the posts that quoted it. Those arrive as full posts, with a
     * discussion relationship and rendered content, so a page must not decide
     * what to render by looking for post-shaped resources in the response.
     *
     * @test
     */
    public function page_does_not_render_a_later_post_that_quotes_one_of_its_posts()
    {
        $body = $this->html('/d/1');

        // Page 1 is posts 1 to 20.
        $this->assertStringContainsString($this->marker(2), $body);
        $this->assertStringContainsString($this->marker(20), $body);

        // Post 21 belongs to page 2. It quotes post 2, and that must not drag
        // it onto page 1.
        $this->assertStringNotContainsString($this->marker(21), $body);
    }
}
