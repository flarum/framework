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
use Illuminate\Support\Arr;

/**
 * A title match makes the discussion's first post its most relevant post,
 * which must not expose a first post the actor cannot see.
 */
class MostRelevantPostVisibilityTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->database()->rollBack();

        // FULLTEXT indexing doesn't happen in transactions, so insert outside
        // of one and clean up explicitly in tearDown().
        $now = Carbon::now()->toDateTimeString();

        $this->database()->table('discussions')->insert([
            ['id' => 1, 'title' => 'spinnaker in title', 'created_at' => $now, 'user_id' => 1, 'comment_count' => 2],
            ['id' => 2, 'title' => 'spinnaker in title too', 'created_at' => $now, 'user_id' => 1, 'comment_count' => 1],
        ]);

        $this->database()->table('posts')->insert([
            ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'created_at' => $now, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>secret hidden first post</p></t>', 'hidden_at' => $now],
            ['id' => 2, 'discussion_id' => 1, 'number' => 2, 'created_at' => $now, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>visible reply</p></t>', 'hidden_at' => null],
            ['id' => 3, 'discussion_id' => 2, 'number' => 1, 'created_at' => $now, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>visible first post</p></t>', 'hidden_at' => null],
        ]);

        // first_post_id references posts, so it can only be set once they exist.
        $this->database()->table('discussions')->where('id', 1)->update(['first_post_id' => 1]);
        $this->database()->table('discussions')->where('id', 2)->update(['first_post_id' => 3]);

        $this->database()->beginTransaction();

        $this->populateDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->database()->table('discussions')->delete();
        $this->database()->table('posts')->delete();
    }

    /**
     * @test
     */
    public function title_search_does_not_expose_hidden_first_post_to_guest()
    {
        $json = $this->search(null);

        $this->assertNull(Arr::get($json['data']['1'], 'relationships.mostRelevantPost.data'));
        $this->assertEquals(['type' => 'posts', 'id' => '3'], Arr::get($json['data']['2'], 'relationships.mostRelevantPost.data'));
        $this->assertNotContains('1', collect($json['included'] ?? [])->where('type', 'posts')->pluck('id')->all());
        $this->assertStringNotContainsString('secret', json_encode($json));
    }

    /**
     * @test
     */
    public function title_search_still_shows_hidden_first_post_to_admin()
    {
        $json = $this->search(1);

        $this->assertEquals(['type' => 'posts', 'id' => '1'], Arr::get($json['data']['1'], 'relationships.mostRelevantPost.data'));
    }

    private function search(?int $actorId): array
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', $actorId ? ['authenticatedAs' => $actorId] : [])
                ->withQueryParams(['filter' => ['q' => 'spinnaker'], 'include' => 'mostRelevantPost'])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true);
        $json['data'] = collect($json['data'])->keyBy('id')->all();

        return $json;
    }
}
