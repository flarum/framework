<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\posts;

use Carbon\Carbon;
use Flarum\Api\ApiKey;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Showing post sources is off until an admin turns it on, but sources are
 * recorded all along, so turning it on shows them for earlier posts too.
 */
class PostSourceDefaultTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now()->subDay(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'created_at' => Carbon::now()->subDay(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t>web</t>'],
            ],
            ApiKey::class => [
                ['key' => 'mastertoken', 'user_id' => null, 'created_at' => Carbon::now()],
            ],
        ]);
    }

    #[Test]
    public function sources_are_hidden_by_default_but_still_recorded()
    {
        $forum = json_decode((string) $this->send($this->request('GET', '/api'))->getBody(), true);
        $this->assertFalse($forum['data']['attributes']['showPostSource']);

        $response = $this->send(
            $this->request('POST', '/api/posts', ['json' => ['data' => [
                'type' => 'posts',
                'attributes' => ['content' => 'A reply made with an API key'],
                'relationships' => ['discussion' => ['data' => ['type' => 'discussions', 'id' => '1']]],
            ]]])->withAddedHeader('Authorization', 'Token mastertoken; userId=2')
        );

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());

        $document = json_decode((string) $response->getBody(), true);
        $this->assertArrayNotHasKey('source', $document['data']['attributes']);
        $this->assertEquals('api', Post::query()->findOrFail($document['data']['id'])->created_via);
    }
}
