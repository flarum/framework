<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\discussions;

use Flarum\Discussion\Discussion;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;
use Illuminate\Support\Arr;

class CreateTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ]
        ]);
    }

    /**
     * @test
     */
    public function cannot_create_discussion_without_content()
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

        // The response body should contain details about the failed validation
        $body = (string) $response->getBody();
        $this->assertJson($body);
        $this->assertEquals([
            'errors' => [
                [
                    'status' => '422',
                    'code' => 'validation_error',
                    'detail' => 'validation.required',
                    'source' => ['pointer' => '/data/attributes/content'],
                ],
            ],
        ], json_decode($body, true));
    }

    /**
     * @test
     */
    public function cannot_create_discussion_without_title()
    {
        $response = $this->send(
            $this->request('POST', '/api/discussions', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'title' => '',
                            'content' => 'Test post',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(422, $response->getStatusCode());

        // The response body should contain details about the failed validation
        $body = (string) $response->getBody();
        $this->assertJson($body);
        $this->assertEquals([
            'errors' => [
                [
                    'status' => '422',
                    'code' => 'validation_error',
                    'detail' => 'validation.required',
                    'source' => ['pointer' => '/data/attributes/title'],
                ],
            ],
        ], json_decode($body, true));
    }

    /**
     * @test
     */
    public function can_create_discussion()
    {
        $response = $this->send(
            $this->request('POST', '/api/discussions', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'title' => 'test - too-obscure',
                            'content' => 'predetermined content for automated testing - too-obscure',
                        ],
                    ]
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        /** @var Discussion $discussion */
        $discussion = Discussion::firstOrFail();
        $data = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals('test - too-obscure', $discussion->title);
        $this->assertEquals('test - too-obscure', Arr::get($data, 'data.attributes.title'));
    }

    /**
     * @test
     */
    public function discussion_creation_limited_by_throttler()
    {
        $this->send(
            $this->request('POST', '/api/discussions', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'title' => 'test - too-obscure',
                            'content' => 'predetermined content for automated testing - too-obscure',
                        ],
                    ]
                ],
            ])
        );

        $response = $this->send(
            $this->request('POST', '/api/discussions', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'title' => 'test - too-obscure',
                            'content' => 'Second predetermined content for automated testing - too-obscure',
                        ],
                    ]
                ],
            ])
        );

        $this->assertEquals(429, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function throttler_doesnt_apply_to_admin()
    {
        $this->send(
            $this->request('POST', '/api/discussions', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'title' => 'test - too-obscure',
                            'content' => 'predetermined content for automated testing - too-obscure',
                        ],
                    ]
                ],
            ])
        );

        $response = $this->send(
            $this->request('POST', '/api/discussions', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'title' => 'test - too-obscure',
                            'content' => 'Second predetermined content for automated testing - too-obscure',
                        ],
                    ]
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());
    }
}
