<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Tests\integration\api;

use Flarum\Extend;
use Flarum\Post\Event\Saving;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Regression tests for https://github.com/flarum/framework/issues/4416.
 *
 * Reading $post->content in a Saving listener must not throw when content
 * is empty/null — HasFormattedContent::getContentAttribute() was passing an
 * empty string to Formatter::unparse() and the mentions unparsers, which
 * have a `string` type hint that does not accept null.
 */
class CreatePostWithEmptyContentTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mentions');

        // Simulate a third-party extension that reads $post->content in a
        // Saving listener — the pattern that triggers the bug.
        $this->extend(
            (new Extend\Event())
                ->listen(Saving::class, function ($event) {
                    $event->post->content;
                })
        );

        $this->prepareDatabase([
            \Flarum\User\User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    #[Test]
    public function creating_discussion_with_empty_content_returns_validation_error(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/discussions', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'title' => 'Test discussion',
                            'content' => '',
                        ],
                    ],
                ],
            ])
        );

        // Must be 422 (validation), not 500 (TypeError from unparse receiving null/empty).
        $this->assertEquals(422, $response->getStatusCode());
    }

    #[Test]
    public function creating_discussion_without_content_returns_validation_error(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/discussions', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'title' => 'Test discussion',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(422, $response->getStatusCode());
    }

    #[Test]
    public function creating_discussion_with_null_content_returns_validation_error(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/discussions', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'title' => 'Test discussion',
                            'content' => null,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(422, $response->getStatusCode());
    }
}
