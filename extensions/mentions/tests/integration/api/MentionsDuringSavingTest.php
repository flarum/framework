<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Post\Event\Saving;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Regression tests for https://github.com/flarum/framework/issues/4823.
 *
 * When `$post->content` is read during the `Saving` lifecycle event (before
 * the post's mention pivot tables have been populated), the mention
 * formatters must still resolve the mentioned entity by its own existence
 * rather than the not-yet-synced pivot — otherwise the mention is wrongly
 * unparsed to the `[deleted]` / `[unknown]` fallback text.
 */
class MentionsDuringSavingTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * Content observed by the Saving listener, captured for assertion.
     */
    protected ?string $observedContent = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mentions');

        $this->extend(
            (new Extend\Event())
                ->listen(Saving::class, function (Saving $event) {
                    // Reading content here triggers unparse() while the post's
                    // mention pivots are not yet synced — the #4823 scenario.
                    $this->observedContent = $event->post->content;
                })
        );

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'mentioned_user', 'email' => 'mentioned_user@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Test discussion', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Original</p></t>'],
            ],
        ]);
    }

    #[Test]
    public function user_mention_resolves_during_saving_of_a_new_post(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '@"mentioned_user"#3',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['type' => 'discussions', 'id' => '1']],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode(), $response->getBody()->getContents());

        // The Saving listener must have seen the real mention, NOT the deleted
        // fallback — this is the regression from #4823.
        $this->assertNotNull($this->observedContent);
        $this->assertStringContainsString('mentioned_user', $this->observedContent);
        $this->assertStringNotContainsString('[deleted]', $this->observedContent);
        $this->assertStringNotContainsString('[unknown]', $this->observedContent);
    }

    #[Test]
    public function user_mention_resolves_during_saving_when_editing_a_post(): void
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/1', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '@"mentioned_user"#3 edited',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());

        $this->assertNotNull($this->observedContent);
        $this->assertStringContainsString('mentioned_user', $this->observedContent);
        $this->assertStringNotContainsString('[deleted]', $this->observedContent);
        $this->assertStringNotContainsString('[unknown]', $this->observedContent);
    }
}
