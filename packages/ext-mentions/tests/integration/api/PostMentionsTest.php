<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Extend;
use Flarum\Post\CommentPost;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\Testing\integration\UsesSettings;
use Flarum\User\DisplayName\DriverInterface;
use Flarum\User\User;

class PostMentionsTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use UsesSettings;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mentions');

        $this->prepareDatabase([
            'users' => [
                ['id' => 3, 'username' => 'potato', 'email' => 'potato@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'toby', 'email' => 'toby@machine.local', 'is_email_confirmed' => 1],
            ],
            'discussions' => [
                ['id' => 2, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 3, 'first_post_id' => 4, 'comment_count' => 2],
            ],
            'posts' => [
                ['id' => 4, 'number' => 2, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'comment', 'content' => '<r><POSTMENTION displayname="TobyFlarum___" id="5" number="2" discussionid="2" username="toby">@tobyuuu#5</POSTMENTION></r>'],
                ['id' => 5, 'number' => 3, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 4, 'type' => 'comment', 'content' => '<r><POSTMENTION displayname="potato" id="4" number="3" discussionid="2" username="potato">@potato#4</POSTMENTION></r>'],
            ],
            'post_mentions_post' => [
                ['post_id' => 4, 'mentions_post_id' => 5],
                ['post_id' => 5, 'mentions_post_id' => 4]
            ],
            'settings' => [
                ['key' => 'display_name_driver', 'value' => 'custom_display_name_driver'],
            ],
        ]);

        $this->extend(
            (new Extend\User)
                ->displayNameDriver('custom_display_name_driver', CustomOtherDisplayNameDriver::class)
        );
    }

    /**
     * Purge the settings cache and reset the new display name driver.
     */
    protected function recalculateDisplayNameDriver()
    {
        $this->purgeSettingsCache();
        $container = $this->app()->getContainer();
        $container->forgetInstance('flarum.user.display_name.driver');
        User::setDisplayNameDriver($container->make('flarum.user.display_name.driver'));
    }

    /**
     * @test
     */
    public function mentioning_a_valid_post_works()
    {
        $this->app();
        $this->recalculateDisplayNameDriver();

        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '@potato#4',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['id' => 2]],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString('POTATO$', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('@potato#4', $response['data']['attributes']['content']);
        $this->assertStringContainsString('PostMention', $response['data']['attributes']['contentHtml']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsPosts->find(4));
    }

    /**
     * @test
     */
    public function mentioning_an_invalid_post_doesnt_work()
    {
        $this->app();
        $this->recalculateDisplayNameDriver();

        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '@franzofflarum#215',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['id' => 2]],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringNotContainsString('FRANZOFFLARUM$', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('@franzofflarum#215', $response['data']['attributes']['content']);
        $this->assertStringNotContainsString('PostMention', $response['data']['attributes']['contentHtml']);
        $this->assertCount(0, CommentPost::find($response['data']['id'])->mentionsPosts);
    }

    /**
     * @test
     */
    public function mentioning_multiple_posts_works()
    {
        $this->app();
        $this->recalculateDisplayNameDriver();

        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '@toby#5 @flarum @franzofflarum#220 @potato @potato#4',
                        ],
                        'relationships' => [
                            'discussion' => ['data' => ['id' => 2]],
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString('TOBY$', $response['data']['attributes']['contentHtml']);
        $this->assertStringNotContainsString('FRANZOFFLARUM$', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('POTATO$', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('@toby#5 @flarum @franzofflarum#220 @potato @potato#4', $response['data']['attributes']['content']);
        $this->assertStringContainsString('PostMention', $response['data']['attributes']['contentHtml']);
        $this->assertCount(2, CommentPost::find($response['data']['id'])->mentionsPosts);
    }

    /**
     * @test
     */
    public function post_mentions_render_with_fresh_data()
    {
        $this->app();
        $this->recalculateDisplayNameDriver();

        $response = $this->send(
            $this->request('GET', '/api/posts/4', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString('TOBY$', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('PostMention', $response['data']['attributes']['contentHtml']);
        $this->assertCount(1, CommentPost::find($response['data']['id'])->mentionsPosts);
    }
}

class CustomOtherDisplayNameDriver implements DriverInterface
{
    public function displayName(User $user): string
    {
        return strtoupper($user->username).'$';
    }
}
