<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\tools;

use Flarum\Discussion\Discussion;
use Flarum\Http\SlugManager;
use Flarum\Mcp\Tests\integration\McpTestCase;
use Flarum\Post\Post;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class SearchDiscussionsTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => [
                ['id' => 1, 'title' => 'By the admin', 'slug' => 'by-the-admin', 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'By normal', 'slug' => 'by-normal', 'user_id' => 2, 'first_post_id' => 2, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Admin</p></t>'],
                ['id' => 2, 'discussion_id' => 2, 'number' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Normal</p></t>'],
            ],
        ]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function slugDrivers(): array
    {
        return ['default' => ['default'], 'id' => ['id'], 'id_with_display_name' => ['id_with_display_name']];
    }

    #[Test]
    #[DataProvider('slugDrivers')]
    public function author_is_a_username_whatever_the_slug_driver(string $driver): void
    {
        $this->setting('slug_driver_'.User::class, $driver);

        $auth = $this->bearer($this->developerToken(2));

        $search = $this->toolOutput($this->callTool('search_discussions', ['author' => 'normal'], $auth));
        $this->assertSame(['2'], array_column($search['data'], 'id'));

        $unknown = $this->toolOutput($this->callTool('search_discussions', ['author' => 'nobody'], $auth));
        $this->assertSame([], $unknown['data']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function discussionSlugDrivers(): array
    {
        return ['default' => ['default'], 'utf8' => ['utf8']];
    }

    #[Test]
    #[DataProvider('discussionSlugDrivers')]
    public function discussion_urls_come_from_the_slug_driver(string $driver): void
    {
        $this->setting('slug_driver_'.Discussion::class, $driver);

        $auth = $this->bearer($this->developerToken(2));
        $slug = $this->app()->getContainer()->make(SlugManager::class)->forResource(Discussion::class)->toSlug(Discussion::query()->findOrFail(2));

        $search = $this->toolOutput($this->callTool('search_discussions', ['author' => 'normal'], $auth));
        $this->assertSame("http://localhost/d/$slug", $search['data'][0]['url']);

        $read = $this->toolOutput($this->callTool('read_discussion', ['id' => '2'], $auth));
        $this->assertSame("http://localhost/d/$slug", $read['discussion']['url']);
    }
}
