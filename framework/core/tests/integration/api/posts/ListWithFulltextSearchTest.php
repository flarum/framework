<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\posts;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

class ListWithFulltextSearchTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->database()->rollBack();

        // FULLTEXT indexing does not happen inside a transaction, so this data is
        // inserted outside one and cleaned up explicitly in tearDown().
        $this->database()->table('discussions')->insert($this->rowsThroughFactory(Discussion::class, [
            ['id' => 1, 'title' => 'first', 'user_id' => 1],
            ['id' => 2, 'title' => 'second', 'user_id' => 1],
        ]));

        $this->database()->table('posts')->insert($this->rowsThroughFactory(Post::class, [
            ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>lightsail in text</p></t>'],
            ['id' => 2, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>nothing relevant here</p></t>'],
            ['id' => 3, 'discussion_id' => 2, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>支持中文吗</p></t>'],
        ]));

        $this->database()->beginTransaction();

        $this->populateDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->database()->table('discussions')->delete();
        $this->database()->table('posts')->delete();
    }

    #[Test]
    public function can_search_posts_by_content()
    {
        if ($this->database()->getDriverName() === 'sqlite') {
            return $this->markTestSkipped('No fulltext search in SQLite.');
        }

        $ids = $this->searchPosts('lightsail');

        $this->assertEqualsCanonicalizing(['1'], $ids);
    }

    #[Test]
    public function cannot_search_cjk_substring_by_default()
    {
        if ($this->database()->getDriverName() === 'sqlite') {
            return $this->markTestSkipped('SQLite already searches by substring.');
        }

        $this->assertEquals([], $this->searchPosts('中文'), 'CJK substring should not be found without CJK search mode.');
    }

    #[Test]
    public function can_search_cjk_substring_with_cjk_mode()
    {
        if ($this->database()->getDriverName() === 'sqlite') {
            return $this->markTestSkipped('SQLite already searches by substring.');
        }

        $this->app()->getContainer()->make(SettingsRepositoryInterface::class)->set('search_cjk_mode', '1');

        $this->assertEqualsCanonicalizing(['3'], $this->searchPosts('中文'), 'CJK substring should be found with CJK search mode.');
    }

    #[Test]
    public function cjk_mode_leaves_normal_search_working()
    {
        if ($this->database()->getDriverName() === 'sqlite') {
            return $this->markTestSkipped('No fulltext search in SQLite.');
        }

        $this->app()->getContainer()->make(SettingsRepositoryInterface::class)->set('search_cjk_mode', '1');

        $this->assertEqualsCanonicalizing(['1'], $this->searchPosts('lightsail'));
    }

    /**
     * @return string[]
     */
    private function searchPosts(string $query): array
    {
        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 1])
                ->withQueryParams(['filter' => ['q' => $query]])
        );

        return Arr::pluck(json_decode($response->getBody()->getContents(), true)['data'], 'id');
    }
}
