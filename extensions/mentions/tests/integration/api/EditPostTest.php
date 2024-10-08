<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Tests\integration\api;

use Flarum\Extend;
use Flarum\Testing\integration\TestCase;

class EditPostTest extends TestCase
{
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mentions');

        $this->prepareDatabase([
            'discussions' => [
                ['id' => 1, 'title' => 'Discussion with post', 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Text</p></t>'],
            ]
        ]);

        $this->extend(
            (new Extend\Event())
            ->listen(\Flarum\Post\Event\Saving::class, function ($event) {
                $event->post->content;
            })
        );
    }

    /**
     * @test
     */
    public function cannot_update_post_with_empty_string()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/1', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(422, $response->getStatusCode());

        $body = (string) $response->getBody();
        $this->assertJson($body);
        $this->assertEquals([
            'errors' => [
                [
                    'status' => '422',
                    'code' => 'validation_error',
                    'detail' => 'The content field is required.',
                    'source' => ['pointer' => '/data/attributes/content'],
                ],
            ],
        ], json_decode($body, true));
    }

    /**
     * @test
     */
    public function cannot_update_post_with_invalid_content_type()
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/1', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => [],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(422, $response->getStatusCode());

        $body = (string) $response->getBody();
        $this->assertJson($body);
        $this->assertEquals([
            'errors' => [
                [
                    'status' => '422',
                    'code' => 'validation_error',
                    'detail' => 'The content field is required.',
                    'source' => ['pointer' => '/data/attributes/content'],
                ],
            ],
        ], json_decode($body, true));
    }
}
