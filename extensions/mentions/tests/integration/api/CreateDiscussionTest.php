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

class CreateDiscussionTest extends TestCase
{
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mentions');

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
    public function cannot_create_discussion_with_empty_string()
    {
        $response = $this->send(
            $this->request('POST', '/api/discussions', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'title' => 'Test post',
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
    public function cannot_create_discussion_without_content_property()
    {
        $response = $this->send(
            $this->request('POST', '/api/discussions', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'title' => 'Test post',
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
    public function cannot_create_discussion_with_content_set_to_null()
    {
        $response = $this->send(
            $this->request('POST', '/api/discussions', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'title' => 'Test post',
                            'content' => null,
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
