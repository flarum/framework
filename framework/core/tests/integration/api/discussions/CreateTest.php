<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\discussions;

use Flarum\Discussion\Discussion;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
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
                    'detail' => 'The content field is required.',
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
                    'detail' => 'The title field is required.',
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
    public function can_create_discussion_with_current_lang_slug_transliteration()
    {
        // Forum default is traditional Chinese.
        $this->setting('default_locale', 'zh');

        $response = $this->send(
            $this->request('POST', '/api/discussions', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'title' => '我是一个土豆',
                            'content' => 'predetermined content for automated testing',
                        ],
                    ]
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        /** @var Discussion $discussion */
        $discussion = Discussion::firstOrFail();

        $this->assertEquals('wo-shi-yi-ge-tu-dou', $discussion->slug);
    }

    /**
     * @test
     */
    public function can_create_discussion_with_forum_locale_transliteration()
    {
        // Forum default is traditional Chinese.
        $this->setting('default_locale', 'zh');
        // Actor locale is English
        $this->app()->getContainer()->make('flarum.locales')->setLocale('en');

        $response = $this->send(
            $this->request('POST', '/api/discussions', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'title' => '我是一个土豆',
                            'content' => 'predetermined content for automated testing',
                        ],
                    ]
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        /** @var Discussion $discussion */
        $discussion = Discussion::firstOrFail();

        $this->assertEquals('wo-shi-yi-ge-tu-dou', $discussion->slug);
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
