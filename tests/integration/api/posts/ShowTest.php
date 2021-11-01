<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\posts;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

class ShowTest extends TestCase
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
                ['id' => 1, 'title' => 'Discussion with post', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'is_private' => 0],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>valid</p></t>'],
                ['id' => 2, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<tMALFORMED'],
            ],
            'users' => [
                $this->normalUser(),
            ]
        ]);
    }

    /**
     * @test
     */
    public function properly_formatted_post_rendered_correctly()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts/1', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = (string) $response->getBody();
        $this->assertJson($body);

        $data = json_decode($body, true);

        $this->assertEquals($data['data']['attributes']['contentHtml'], '<p>valid</p>');
    }

    /**
     * @test
     */
    public function malformed_post_caught_by_renderer()
    {
        $response = $this->send(
            $this->request('GET', '/api/posts/2', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = (string) $response->getBody();
        $this->assertJson($body);

        $data = json_decode($body, true);

        $this->assertEquals("Sorry, we encountered an error while displaying this content. If you're a user, please try again later. If you're an administrator, take a look in your Flarum log files for more information.", $data['data']['attributes']['contentHtml']);
    }
}
