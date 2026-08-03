<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\frontend;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Str;

class DiscussionPageContentTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $posts = [];

        // Markers are zero padded so that no marker is a prefix of another one,
        // which keeps assertStringNotContainsString honest.
        for ($i = 1; $i <= 30; $i++) {
            $posts[] = [
                'id' => $i,
                'number' => $i,
                'discussion_id' => 1,
                'created_at' => Carbon::parse('2024-01-01 00:00:00')->addMinutes($i)->toDateTimeString(),
                'user_id' => 2,
                'type' => 'comment',
                'content' => '<t><p>POST-MARKER-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT).'</p></t>',
            ];
        }

        $this->prepareDatabase([
            'discussions' => [
                ['id' => 1, 'title' => 'Paginated discussion', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 1, 'last_post_id' => 30, 'comment_count' => 30, 'is_private' => 0],
            ],
            'posts' => $posts,
            'users' => [
                $this->normalUser(),
            ],
        ]);
    }

    /**
     * The rendered markup, without the JSON payload that follows it.
     *
     * The markup is what a client with no JavaScript, and a crawler, reads as
     * the page. The payload after it is the data the JS app boots from, which
     * still carries the posts surrounding the one being linked to so that the
     * app can scroll to it, and is not page text.
     */
    private function html(string $path, array $query = []): string
    {
        $request = $this->request('GET', $path);

        if ($query) {
            $request = $request->withQueryParams($query);
        }

        $body = $this->send($request)->getBody()->getContents();

        return Str::before($body, '<script id="flarum-json-payload"');
    }

    private function marker(int $number): string
    {
        return 'POST-MARKER-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT);
    }

    /**
     * @test
     */
    public function first_page_renders_only_the_first_twenty_posts()
    {
        $body = $this->html('/d/1');

        $this->assertStringContainsString($this->marker(1), $body);
        $this->assertStringContainsString($this->marker(20), $body);
        $this->assertStringNotContainsString($this->marker(21), $body);
        $this->assertStringNotContainsString($this->marker(30), $body);
    }

    /**
     * @test
     */
    public function second_page_renders_only_the_second_page_of_posts()
    {
        $body = $this->html('/d/1', ['page' => 2]);

        $this->assertStringNotContainsString($this->marker(1), $body);
        $this->assertStringNotContainsString($this->marker(20), $body);
        $this->assertStringContainsString($this->marker(21), $body);
        $this->assertStringContainsString($this->marker(30), $body);
    }

    /**
     * A discussion page must not serve content that belongs to another page.
     * A numbered URL declares a canonical page, so the page it renders has to
     * be that one, otherwise a crawler indexes one page's posts under another
     * page's URL.
     *
     * @test
     */
    public function numbered_url_renders_the_page_it_declares_as_canonical()
    {
        $body = $this->html('/d/1/25');

        $this->assertStringContainsString('?page=2', $body, 'Expected the numbered URL to declare page 2 as its canonical URL.');

        // Post 25 sits on page 2, so page 2 is what should be rendered.
        $this->assertStringContainsString($this->marker(21), $body);
        $this->assertStringContainsString($this->marker(30), $body);

        // Posts 15 to 20 belong to page 1, and must not appear on a page whose
        // canonical URL says it is page 2.
        $this->assertStringNotContainsString($this->marker(15), $body);
        $this->assertStringNotContainsString($this->marker(20), $body);
    }
}
