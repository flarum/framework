<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\discussions;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

class DeletionTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'discussions' => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>'],
            ],
            'users' => [
                $this->normalUser(),
            ],
        ]);
    }

    /**
     * @test
     */
    public function admin_can_delete()
    {
        $response = $this->send(
            $this->request('DELETE', '/api/discussions/1', [
                'authenticatedAs' => 1,
                'json' => [],
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());

        // Ensure both the database and the corresponding post are deleted
        $this->assertNull($this->database()->table('discussions')->find(1), 'Discussion exists in the DB');
        $this->assertNull($this->database()->table('posts')->find(1), 'Post exists in the DB');
    }
}
