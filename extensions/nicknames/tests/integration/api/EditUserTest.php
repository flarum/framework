<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Nicknames\Tests\integration;

use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class UpdateTest extends TestCase
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
            'users' => [
                $this->normalUser(),
            ],
        ]);
    }

    /**
     * @test
     */
    public function user_cant_edit_own_nickname_if_not_allowed()
    {
        $this->database()->table('group_permission')->where('permission', 'user.editOwnNickname')->where('group_id', Group::MEMBER_ID)->delete();

        $response = $this->send(
            $this->request('PATCH', '/api/users/2', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'attributes' => [
                            'nickname' => 'new nickname',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
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
                        'attributes' => [
                            'nickname' => 'new nickname',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('new nickname', User::find(2)->nickname);
    }

    /**
     * @test
     */
    public function nickname_with_dots_is_allowed()
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
                        'attributes' => [
                            'nickname' => 'jane.smith',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('jane.smith', User::find(2)->nickname);
    }

    /**
     * @test
     * @dataProvider markdownInjectionNicknames
     */
    public function nickname_with_markdown_injection_chars_is_rejected(string $nickname)
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
                        'attributes' => [
                            'nickname' => $nickname,
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertNull(User::find(2)->nickname);
    }

    public function markdownInjectionNicknames(): array
    {
        return [
            'markdown link syntax' => ['[CLICK](https://evil.com)'],
            'square brackets only' => ['[username]'],
            'angle brackets' => ['<evil.com>'],
            'parentheses' => ['evil(com)'],
        ];
    }
}
