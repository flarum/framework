<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\discussions;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Covers `POST /api/discussions/read` with a fulltext `filter[q]`.
 *
 * `q` is a narrowing filter like any other: the handler runs it back through
 * the searcher, so a search marks only the discussions it matches read and
 * never the whole forum. Fulltext indexing does not happen inside a
 * transaction, so the rows are inserted outside one and cleaned up explicitly
 * in tearDown(), the same way `ListWithFulltextSearchTest` does.
 */
class ReadAllWithFulltextSearchTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->database()->rollBack();

        $this->database()->table('discussions')->insert($this->rowsThroughFactory(Discussion::class, [
            // Matches a search for "lightsail" on its title.
            ['id' => 1, 'title' => 'lightsail in title', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'comment_count' => 1, 'last_post_number' => 1],
            // Matches nothing a search for "lightsail" looks at.
            ['id' => 2, 'title' => 'not in title or text', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'comment_count' => 1, 'last_post_number' => 1],
        ]));

        $this->database()->table('posts')->insert($this->rowsThroughFactory(Post::class, [
            ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'number' => 1, 'content' => '<t><p>not in text</p></t>'],
            ['id' => 2, 'discussion_id' => 2, 'user_id' => 1, 'type' => 'comment', 'number' => 1, 'content' => '<t><p>not in title or text</p></t>'],
        ]));

        // Re-open the transaction `::app()` rolled back above, then seed.
        $this->database()->beginTransaction();

        $this->populateDatabase();
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->database()->table('discussions')->delete();
        $this->database()->table('posts')->delete();
    }

    #[Test]
    public function a_fulltext_search_marks_only_the_matching_discussions_read()
    {
        if ($this->database()->getDriverName() === 'sqlite') {
            return $this->markTestSkipped('No fulltext search in SQLite.');
        }

        $response = $this->send(
            $this->request('POST', '/api/discussions/read', ['authenticatedAs' => 1])
                ->withQueryParams(['filter' => ['q' => 'lightsail']])
        );

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());

        // Discussion 1's title matched, so it is read up to its last post.
        $this->assertEquals(
            1,
            $this->database()->table('discussion_user')->where(['user_id' => 1, 'discussion_id' => 1])->value('last_read_post_number'),
            'The discussion the search matched should be marked read.'
        );

        // Discussion 2 matched nothing, so it is left untouched.
        $this->assertNull(
            $this->database()->table('discussion_user')->where(['user_id' => 1, 'discussion_id' => 2])->first(),
            'A discussion the search did not match must not be marked read.'
        );

        // A fulltext search narrows the list, so the whole-forum timestamp
        // must stay unset — only the matched rows are written.
        $this->assertNull($this->database()->table('users')->find(1)->marked_all_as_read_at);
    }
}
