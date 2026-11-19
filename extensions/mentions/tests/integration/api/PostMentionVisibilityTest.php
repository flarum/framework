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
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class PostMentionVisibilityTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mentions');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
            Discussion::class => [
                ['id' => 2, 'title' => 'Public discussion', 'slug' => 'public-discussion', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 100, 'last_post_id' => 101, 'last_post_number' => 2, 'comment_count' => 2],
                ['id' => 50, 'title' => 'Private discussion', 'slug' => 'private-discussion', 'is_private' => true, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 50, 'comment_count' => 1],
                ['id' => 60, 'title' => 'Hidden discussion', 'slug' => 'hidden-discussion', 'hidden_at' => Carbon::now(), 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 60, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 50, 'number' => 1, 'discussion_id' => 50, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t>private</t>'],
                ['id' => 60, 'number' => 1, 'discussion_id' => 60, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t>hidden</t>'],
                ['id' => 100, 'number' => 1, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t>first</t>'],
                ['id' => 101, 'number' => 2, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<r><POSTMENTION displayname="admin" id="50" number="1" discussionid="50">@"admin"#p50</POSTMENTION> <POSTMENTION displayname="admin" id="60" number="1" discussionid="60">@"admin"#p60</POSTMENTION> <POSTMENTION displayname="admin" id="100" number="1" discussionid="2">@"admin"#p100</POSTMENTION> <POSTMENTION displayname="admin" id="102" number="3" discussionid="2">@"admin"#p102</POSTMENTION> <POSTMENTION displayname="gone" id="999" number="9" discussionid="2">@"gone"#p999</POSTMENTION></r>'],
                ['id' => 102, 'number' => 3, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t>hidden post</t>', 'hidden_at' => Carbon::now()],
            ],
            'post_mentions_post' => [
                ['post_id' => 101, 'mentions_post_id' => 50],
                ['post_id' => 101, 'mentions_post_id' => 60],
                ['post_id' => 101, 'mentions_post_id' => 100],
                ['post_id' => 101, 'mentions_post_id' => 102],
            ],
        ]);
    }

    public static function endpointsRenderingTheMentioningPost(): array
    {
        return [
            'show post' => ['/api/posts/101', []],
            'list posts' => ['/api/posts', ['filter' => ['discussion' => 2]]],
            'show discussion' => ['/api/discussions/2', []],
            'mentioned by' => ['/api/posts/100', ['include' => 'mentionedBy']],
        ];
    }

    #[Test]
    #[DataProvider('endpointsRenderingTheMentioningPost')]
    public function mentions_of_posts_the_actor_cannot_see_do_not_link_to_their_discussion(string $path, array $query)
    {
        foreach ([null, 2] as $actorId) {
            $html = $this->contentHtmlOfPost101($path, $query, $actorId);

            $this->assertStringNotContainsString('private-discussion', $html);
            $this->assertStringNotContainsString('/d/50', $html);
            $this->assertStringNotContainsString('hidden-discussion', $html);
            $this->assertStringNotContainsString('/d/60', $html);
            $this->assertStringContainsString('PostMention--deleted" data-id="50"', $html);
            $this->assertStringContainsString('PostMention--deleted" data-id="60"', $html);
            $this->assertStringContainsString('PostMention--deleted" data-id="102"', $html);
            $this->assertStringContainsString('PostMention--deleted" data-id="999"', $html);
            $this->assertStringContainsString('/d/2-public-discussion/1', $html);
        }
    }

    #[Test]
    #[DataProvider('endpointsRenderingTheMentioningPost')]
    public function mentions_of_posts_the_actor_can_see_link_to_their_discussion(string $path, array $query)
    {
        // Admins can see hidden discussions, but not core's private ones.
        $html = $this->contentHtmlOfPost101($path, $query, 1);

        $this->assertStringContainsString('PostMention--deleted" data-id="50"', $html);

        $this->assertStringContainsString('/d/60-hidden-discussion/1', $html);
        $this->assertStringContainsString('/d/2-public-discussion/3', $html);
        $this->assertStringContainsString('/d/2-public-discussion/1', $html);
    }

    public static function rendersWithoutAViewer(): array
    {
        return [
            'no request' => [null],
            'a request without an actor' => [new ServerRequest([], [], '/', 'GET')],
        ];
    }

    /**
     * Emails render without a viewer. As core does for links to discussions,
     * mentions keep their link but only name discussions guests can see.
     */
    #[Test]
    #[DataProvider('rendersWithoutAViewer')]
    public function renders_without_a_viewer_link_posts_guests_cannot_see_by_discussion_id_only(?ServerRequest $request)
    {
        $this->app();

        $html = CommentPost::query()->findOrFail(101)->formatContent($request);

        $this->assertStringNotContainsString('private-discussion', $html);
        $this->assertStringNotContainsString('hidden-discussion', $html);
        $this->assertStringContainsString('<a href="http://localhost/d/50/1" class="PostMention" data-id="50"><i class="icon fas fa-reply"></i>admin</a>', $html);
        $this->assertStringContainsString('<a href="http://localhost/d/60/1" class="PostMention" data-id="60"><i class="icon fas fa-reply"></i>admin</a>', $html);
        $this->assertStringContainsString('<a href="http://localhost/d/2/3" class="PostMention" data-id="102"><i class="icon fas fa-reply"></i>admin</a>', $html);
        $this->assertStringContainsString('/d/2-public-discussion/1', $html);
        $this->assertStringContainsString('PostMention--deleted" data-id="999"', $html);
    }

    private function contentHtmlOfPost101(string $path, array $query, ?int $actorId): string
    {
        $response = $this->send(
            $this->request('GET', $path, $actorId ? ['authenticatedAs' => $actorId] : [])->withQueryParams($query)
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $document = json_decode($body, true);
        $resources = array_merge(isset($document['data'][0]) ? $document['data'] : [$document['data']], $document['included'] ?? []);

        foreach ($resources as $resource) {
            if ($resource['type'] === 'posts' && $resource['id'] === '101') {
                return $resource['attributes']['contentHtml'];
            }
        }

        $this->fail("Post 101 is not in the response to $path");
    }
}
