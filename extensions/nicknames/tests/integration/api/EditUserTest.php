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
            User::class => [
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

    /**
     * @test
     */
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
}
