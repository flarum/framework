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

class UserMentionsTest extends TestCase
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
                $this->normalUser(),
                ['id' => 3, 'username' => 'potato', 'email' => 'potato@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'toby', 'email' => 'toby@machine.local', 'is_email_confirmed' => 1],
                ['id' => 5, 'username' => 'bad_user', 'email' => 'bad_user@machine.local', 'is_email_confirmed' => 1],
            ],
            'discussions' => [
                ['id' => 2, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 3, 'first_post_id' => 4, 'comment_count' => 2],
            ],
            'posts' => [
                ['id' => 4, 'number' => 2, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 3, 'type' => 'comment', 'content' => '<r><USERMENTION displayname="TobyFlarum___" id="4" username="toby">@tobyuuu</USERMENTION></r>'],
                ['id' => 6, 'number' => 3, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 4, 'type' => 'comment', 'content' => '<r><USERMENTION displayname="i_am_a_deleted_user" id="2021" username="i_am_a_deleted_user">@"i_am_a_deleted_user"#2021</USERMENTION></r>'],
                ['id' => 10, 'number' => 11, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 5, 'type' => 'comment', 'content' => '<r><USERMENTION displayname="Bad &quot;#p6 User" id="5">@"Bad "#p6 User"#5</USERMENTION></r>'],
            ],
            'post_mentions_user' => [
                ['post_id' => 4, 'mentions_user_id' => 4],
                ['post_id' => 10, 'mentions_user_id' => 5]
            ],
            'settings' => [
                ['key' => 'display_name_driver', 'value' => 'custom_display_name_driver'],
            ],
        ]);

        $this->extend(
            (new Extend\User)
                ->displayNameDriver('custom_display_name_driver', CustomDisplayNameDriver::class)
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
    public function mentioning_a_valid_user_with_old_format_doesnt_work_if_off()
    {
        $this->prepareDatabase([
            'settings' => [
                ['key' => 'flarum-mentions.allow_username_format', 'value' => '0']
            ]
        ]);

        $this->app();
        $this->recalculateDisplayNameDriver();

        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '@potato',
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

        $this->assertStringNotContainsString('@POTATO$', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('@potato', $response['data']['attributes']['content']);
        $this->assertStringNotContainsString('UserMention', $response['data']['attributes']['contentHtml']);
        $this->assertCount(0, CommentPost::find($response['data']['id'])->mentionsUsers);
    }

    /**
     * @test
     */
    public function mentioning_a_valid_user_with_old_format_works_if_on()
    {
        $this->prepareDatabase([
            'settings' => [
                ['key' => 'flarum-mentions.allow_username_format', 'value' => '1']
            ]
        ]);

        $this->app();
        $this->recalculateDisplayNameDriver();

        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '@potato',
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

        $this->assertStringContainsString('@POTATO$', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('@"POTATO$"#3', $response['data']['attributes']['content']);
        $this->assertStringContainsString('UserMention', $response['data']['attributes']['contentHtml']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsUsers->find(3));
    }

    /**
     * @test
     */
    public function mentioning_a_valid_user_with_new_format_works()
    {
        $this->app();
        $this->recalculateDisplayNameDriver();

        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '@"POTATO$"#3',
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

        $this->assertStringContainsString('@POTATO$', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('@"POTATO$"#3', $response['data']['attributes']['content']);
        $this->assertStringContainsString('UserMention', $response['data']['attributes']['contentHtml']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsUsers->find(3));
    }

    /**
     * @test
     */
    public function mentioning_an_invalid_user_doesnt_work()
    {
        $this->app();
        $this->recalculateDisplayNameDriver();

        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '@"franzofflarum"#82',
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

        $this->assertStringNotContainsString('@FRANZOFFLARUM$', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('@"franzofflarum"#82', $response['data']['attributes']['content']);
        $this->assertStringNotContainsString('UserMention', $response['data']['attributes']['contentHtml']);
        $this->assertCount(0, CommentPost::find($response['data']['id'])->mentionsUsers);
    }

    /**
     * @test
     */
    public function mentioning_multiple_users_works()
    {
        $this->app();
        $this->recalculateDisplayNameDriver();

        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '@"TOBY$"#4 @"POTATO$"#p4 @"franzofflarum"#82 @"POTATO$"#3',
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

        $this->assertStringContainsString('@TOBY$', $response['data']['attributes']['contentHtml']);
        $this->assertStringNotContainsString('@FRANZOFFLARUM$', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('@POTATO$', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('@"TOBY$"#4 @"POTATO$"#p4 @"franzofflarum"#82 @"POTATO$"#3', $response['data']['attributes']['content']);
        $this->assertStringContainsString('UserMention', $response['data']['attributes']['contentHtml']);
        $this->assertCount(2, CommentPost::find($response['data']['id'])->mentionsUsers);
    }

    /**
     * @test
     */
    public function old_user_mentions_still_render()
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

        $this->assertStringContainsString('@TOBY$', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('UserMention', $response['data']['attributes']['contentHtml']);
        $this->assertCount(1, CommentPost::find($response['data']['id'])->mentionsUsers);
    }

    /**
     * @test
     */
    public function user_mentions_render_with_fresh_data()
    {
        $this->app();
        $this->recalculateDisplayNameDriver();

        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '@"potato_"#3',
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

        $this->assertStringContainsString('@POTATO$', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('UserMention', $response['data']['attributes']['contentHtml']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsUsers->find(3));
    }

    /**
     * @test
     */
    public function user_mentions_unparse_with_fresh_data()
    {
        $this->app();
        $this->recalculateDisplayNameDriver();

        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '@"potato_"#3',
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

        $this->assertStringContainsString('@"POTATO$"#3', $response['data']['attributes']['content']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsUsers->find(3));
    }

    /**
     * @test
     */
    public function deleted_user_mentions_unparse_and_render_without_user_data()
    {
        $this->app();
        $this->recalculateDisplayNameDriver();
        $deleted_text = $this->app()->getContainer()->make('translator')->trans('core.lib.username.deleted_text');

        $response = $this->send(
            $this->request('GET', '/api/posts/6', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString("@$deleted_text", $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('@"'.$deleted_text.'"#2021', $response['data']['attributes']['content']);
        $this->assertStringContainsString('UserMention', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('UserMention--deleted', $response['data']['attributes']['contentHtml']);
        $this->assertStringNotContainsString('i_am_a_deleted_user', $response['data']['attributes']['contentHtml']);
        $this->assertStringNotContainsString('i_am_a_deleted_user', $response['data']['attributes']['content']);
        $this->assertCount(0, CommentPost::find($response['data']['id'])->mentionsUsers);
    }

    /**
     * @test
     */
    public function user_mentions_with_unremoved_bad_string_from_display_names_doesnt_work()
    {
        $this->app();
        $this->recalculateDisplayNameDriver();

        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '@"Bad "#p6 User"#5',
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

        $this->assertStringNotContainsString('Bad "#p6 User', $response['data']['attributes']['contentHtml']);
        $this->assertNotEquals('@"Bad "#p6 User"#5', $response['data']['attributes']['content']);
        $this->assertStringNotContainsString('UserMention', $response['data']['attributes']['contentHtml']);
        $this->assertNull(CommentPost::find($response['data']['id'])->mentionsUsers->find(5));
    }

    /**
     * @test
     */
    public function user_mentions_unparsing_removes_bad_display_name_string()
    {
        $this->app();
        $this->recalculateDisplayNameDriver();

        $response = $this->send(
            $this->request('GET', '/api/posts/10', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = json_decode($response->getBody(), true);

        $this->assertStringContainsString('Bad "#p6 User', $response['data']['attributes']['contentHtml']);
        $this->assertStringContainsString('@"Bad _ User"#5', $response['data']['attributes']['content']);
        $this->assertStringContainsString('UserMention', $response['data']['attributes']['contentHtml']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsUsers->find(5));
    }

    /**
     * @test
     */
    public function user_mentions_with_removed_bad_string_from_display_names_works()
    {
        $this->app();
        $this->recalculateDisplayNameDriver();

        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'content' => '@"Bad _ User"#5',
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

        $this->assertStringContainsString('Bad "#p6 User', $response['data']['attributes']['contentHtml']);
        $this->assertEquals('@"Bad _ User"#5', $response['data']['attributes']['content']);
        $this->assertStringContainsString('UserMention', $response['data']['attributes']['contentHtml']);
        $this->assertNotNull(CommentPost::find($response['data']['id'])->mentionsUsers->find(5));
    }
}

class CustomDisplayNameDriver implements DriverInterface
{
    public function displayName(User $user): string
    {
        if ($user->username === 'bad_user') {
            return 'Bad "#p6 User';
        }

        return strtoupper($user->username).'$';
    }
}
