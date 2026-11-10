<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Nicknames\Tests\integration;

use Flarum\Group\Group;
use Flarum\Locale\TranslatorInterface;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class EditUserTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-nicknames');
        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    #[Test]
    public function user_cant_edit_own_nickname_if_not_allowed()
    {
        $this->database()->table('group_permission')->where('permission', 'user.editOwnNickname')->where('group_id', Group::MEMBER_ID)->delete();

        $response = $this->send(
            $this->request('PATCH', '/api/users/2', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'users',
                        'attributes' => [
                            'nickname' => 'new nickname',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(403, $response->getStatusCode(), $response->getBody()->getContents());
    }

    #[Test]
    public function user_can_edit_own_nickname_if_allowed()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'user.editOwnNickname', 'group_id' => 2],
            ]
        ]);

        $response = $this->send(
            $this->request('PATCH', '/api/users/2', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'users',
                        'attributes' => [
                            'nickname' => 'new nickname',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());

        $this->assertEquals('new nickname', User::find(2)->nickname);
    }

    #[Test]
    public function cant_edit_nickname_if_invalid_regex()
    {
        $this->setting('flarum-nicknames.set_on_registration', true);
        $this->setting('flarum-nicknames.regex', '^[A-z]+$');

        $response = $this->send(
            $this->request('PATCH', '/api/users/2', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'users',
                        'attributes' => [
                            'nickname' => '007',
                        ],
                    ],
                ],
            ])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(422, $response->getStatusCode(), $body);
        $this->assertStringContainsString($this->app()->getContainer()->make(TranslatorInterface::class)->trans('flarum-nicknames.api.invalid_nickname_message'), $body);
    }

    #[Test]
    public function nickname_with_dots_is_allowed(): void
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'user.editOwnNickname', 'group_id' => Group::MEMBER_ID],
            ]
        ]);

        $response = $this->send(
            $this->request('PATCH', '/api/users/2', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'users',
                        'attributes' => [
                            'nickname' => 'jane.smith',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());
        $this->assertEquals('jane.smith', User::find(2)->nickname);
    }

    #[Test]
    #[DataProvider('nicknamesWithInjectionChars')]
    public function nickname_with_injection_chars_is_rejected(string $nickname): void
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'user.editOwnNickname', 'group_id' => Group::MEMBER_ID],
            ]
        ]);

        $response = $this->send(
            $this->request('PATCH', '/api/users/2', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'users',
                        'attributes' => [
                            'nickname' => $nickname,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(422, $response->getStatusCode(), $response->getBody()->getContents());
        $this->assertNull(User::find(2)->nickname);
    }

    public static function nicknamesWithInjectionChars(): array
    {
        return [
            'markdown link syntax' => ['[CLICK](https://evil.com)'],
            'square brackets only' => ['[username]'],
            'angle brackets' => ['<evil.com>'],
            'parentheses' => ['evil(com)'],
            'html open tag' => ['<script>'],
            'html attribute inject' => ['"><img src=x>'],
        ];
    }

    // Regression test for the TypeError raised by UserResourceFields when an
    // admin saves the nicknames config form with an empty min or max field —
    // the empty string is persisted to the settings table and then passed
    // straight to Schema\Str::minLength(int)/maxLength(int), which rejects
    // non-numeric strings under PHP's default type coercion rules.
    //
    //   TypeError: Str::maxLength(): Argument #1 ($length) must be of type int, string given
    //
    // Numeric strings like '3' coerce silently, so the bug only surfaces when
    // a field is cleared. The int defaults from the Settings extender mask it
    // until the admin saves once.
    #[Test]
    public function request_succeeds_when_min_or_max_setting_is_empty_string(): void
    {
        $this->setting('flarum-nicknames.min', '');
        $this->setting('flarum-nicknames.max', '');

        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'user.editOwnNickname', 'group_id' => Group::MEMBER_ID],
            ],
        ]);

        $response = $this->send(
            $this->request('PATCH', '/api/users/2', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'users',
                        'attributes' => [
                            'nickname' => 'jane.smith',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());
        $this->assertEquals('jane.smith', User::find(2)->nickname);
    }
}
