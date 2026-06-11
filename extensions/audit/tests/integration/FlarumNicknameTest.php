<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

class FlarumNicknameTest extends TestCase
{
    public function setUp(): void
    {
        // The optionalDependency tree doesn't seem to have any effect on tests
        // Therefore we need to load this extension before Audit Log
        $this->extension('flarum-nicknames');

        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                [
                    'id' => 3,
                    'username' => 'user3',
                    'email' => 'user3@example.com',
                    'nickname' => 'User 3',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function update()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/users/3', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'nickname' => 'User 33',
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('user.nickname_changed', [
            'user_id' => 3,
            'old_nickname' => 'User 3',
            'new_nickname' => 'User 33',
        ]);
    }
}
