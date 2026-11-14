<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Database\ConnectionInterface;
use PHPUnit\Framework\Attributes\Test;

class MentionRenderingQueriesTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mentions');

        $posts = [];
        $postMentions = [];
        $userMentions = [];

        // Six posts by the admin, each replied to by a post that mentions it
        // and the admin. The display names stored in the replies are stale, so
        // the tests can tell that rendering looked the mentioned models up.
        for ($i = 1; $i <= 6; $i++) {
            $posts[] = [
                'id' => $i, 'number' => $i, 'discussion_id' => 1, 'created_at' => Carbon::now(),
                'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>text</p></t>',
            ];
        }

        for ($i = 7; $i <= 12; $i++) {
            $mentioned = $i - 6;

            $posts[] = [
                'id' => $i, 'number' => $i, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment',
                'content' => '<r><POSTMENTION displayname="Stale" id="'.$mentioned.'" number="'.$mentioned.'" discussionid="1">@"Stale"#p'.$mentioned.'</POSTMENTION> <USERMENTION displayname="Stale" id="1">@"Stale"#1</USERMENTION> reply</r>',
            ];
            $postMentions[] = ['post_id' => $i, 'mentions_post_id' => $mentioned];
            $userMentions[] = ['post_id' => $i, 'mentions_user_id' => 1];
        }

        // Several mentions in one post, including one of a post that no longer exists.
        $posts[] = [
            'id' => 13, 'number' => 13, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment',
            'content' => '<r>'
                .'<POSTMENTION displayname="Stale" id="1" number="1" discussionid="1">@"Stale"#p1</POSTMENTION> '
                .'<POSTMENTION displayname="Stale" id="8" number="8" discussionid="1">@"Stale"#p8</POSTMENTION> '
                .'<POSTMENTION displayname="Stale" id="999" number="99" discussionid="1">@"Stale"#p999</POSTMENTION> '
                .'<USERMENTION displayname="Stale" id="1">@"Stale"#1</USERMENTION> '
                .'<USERMENTION displayname="Stale" id="2">@"Stale"#2</USERMENTION>'
                .'</r>',
        ];

        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 13],
            ],
            Post::class => $posts,
            'post_mentions_post' => $postMentions,
            'post_mentions_user' => $userMentions,
            User::class => [$this->normalUser()],
        ]);
    }

    #[Test]
    public function included_mentioning_posts_are_rendered_without_a_query_per_mention(): void
    {
        $response = null;

        // As the admin, who can edit the replies, so their source is unparsed
        // as well as their HTML rendered.
        $queries = $this->queriesDuring(function () use (&$response) {
            $response = $this->send(
                $this->request('GET', '/api/posts', ['authenticatedAs' => 1])
                    ->withQueryParams(['filter' => ['id' => '1,2,3,4,5,6']])
            );
        });

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertSame(0, $this->countMatching($queries, 'from "posts" where "posts"."id" = '), 'Mentioned posts were fetched one at a time.');
        $this->assertLessThanOrEqual(1, $this->countMatching($queries, 'from "users" where "users"."id" = '), 'Mentioned users were fetched one at a time.');
        $this->assertLessThanOrEqual(1, $this->countMatching($queries, 'from "discussions" where "discussions"."id" = '), 'Discussions of mentioned posts were fetched one at a time.');

        $body = json_decode($response->getBody()->getContents(), true);
        $reply = collect($body['included'])->first(fn ($resource) => $resource['type'] === 'posts' && $resource['id'] === '7');

        $this->assertNotNull($reply, 'The mentioning post should be included.');
        $this->assertStringContainsString('admin', $reply['attributes']['contentHtml']);
        $this->assertStringNotContainsString('Stale', $reply['attributes']['contentHtml']);
        $this->assertStringContainsString('@"admin"#p1', $reply['attributes']['content']);
        $this->assertStringContainsString('@"admin"#1', $reply['attributes']['content']);
    }

    // Extensions render posts outside the API too, without loading what the
    // mentions need first; the post's mentions are then looked up together.
    #[Test]
    public function rendering_a_post_looks_its_mentions_up_together(): void
    {
        $post = $this->freshPost(13);
        $html = null;

        $queries = $this->queriesDuring(function () use ($post, &$html) {
            $html = $post->formatContent();
        });

        $this->assertSame(0, $this->countMatching($queries, '"posts"."id" = '), 'Mentioned posts were fetched one at a time.');
        $this->assertSame(0, $this->countMatching($queries, '"users"."id" = '), 'Mentioned users were fetched one at a time.');
        $this->assertSame(0, $this->countMatching($queries, '"discussions"."id" = '), 'Discussions of mentioned posts were fetched one at a time.');

        $this->assertStringContainsString('admin', $html);
        $this->assertStringContainsString('normal', $html);
        $this->assertStringNotContainsString('Stale', $html);
        // The mention of a post that no longer exists still renders, as deleted.
        $this->assertSame(1, substr_count($html, 'PostMention--deleted'));
    }

    #[Test]
    public function unparsing_a_post_looks_its_mentions_up_together(): void
    {
        $post = $this->freshPost(13);
        $content = null;

        $queries = $this->queriesDuring(function () use ($post, &$content) {
            $content = $post->content;
        });

        $this->assertSame(0, $this->countMatching($queries, '"posts"."id" = '), 'Mentioned posts were fetched one at a time.');
        $this->assertSame(0, $this->countMatching($queries, '"users"."id" = '), 'Mentioned users were fetched one at a time.');

        $this->assertStringContainsString('@"admin"#p1', $content);
        $this->assertStringContainsString('@"normal"#p8', $content);
        $this->assertStringContainsString('@"admin"#1', $content);
        $this->assertStringContainsString('@"normal"#2', $content);
    }

    #[Test]
    public function rendering_a_post_without_mentions_looks_nothing_up(): void
    {
        $post = $this->freshPost(1);

        $queries = $this->queriesDuring(fn () => $post->formatContent());

        $this->assertSame([], $queries);
    }

    /**
     * The post as an extension would load it: no relations.
     */
    private function freshPost(int $id): CommentPost
    {
        $this->app();

        return CommentPost::query()->findOrFail($id);
    }

    /**
     * @return string[] the SQL run while the callback executed
     */
    private function queriesDuring(callable $callback): array
    {
        $queries = [];
        $listening = true;

        $this->app()->getContainer()->make(ConnectionInterface::class)
            ->listen(function ($query) use (&$queries, &$listening) {
                if ($listening) {
                    $queries[] = $query->sql;
                }
            });

        $callback();
        $listening = false;

        return $queries;
    }

    /**
     * @param string[] $queries
     */
    private function countMatching(array $queries, string $needle): int
    {
        // Identifier quoting differs between drivers, so compare loosely.
        $normalise = fn (string $sql) => str_replace(['`', '"', '[', ']'], '"', $sql);
        $needle = $normalise($needle);

        return count(array_filter($queries, fn (string $sql) => str_contains($normalise($sql), $needle)));
    }
}
