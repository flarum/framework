<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Nicknames\Tests\integration;

use Flarum\Audit\Tests\integration\InteractsWithAuditLog;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class AuditTest extends TestCase
{
    use InteractsWithAuditLog;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpAuditLog();

        // Nicknames must be enabled (and its saving listener registered) alongside audit so the
        // nickname change is captured. Order is not significant here — both boot before the request.
        $this->extension('flarum-audit', 'flarum-nicknames');

        $this->prepareDatabase([
            User::class => [
                [
                    'id' => 3,
                    'username' => 'user3',
                    'email' => 'user3@example.com',
                    'nickname' => 'User 3',
                ],
            ],
        ]);
    }

    #[Test]
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

    #[Test]
    public function registering_with_a_nickname_does_not_error_and_is_logged()
    {
        $this->setting('flarum-nicknames.set_on_registration', true);

        // Registration creates a user with a nickname; there is no original
        // value, so the saving listener must handle a null original (regression
        // for the TypeError in flarum/framework#4733).
        $response = $this->send(
            $this->request('POST', '/register', [
                'json' => [
                    'nickname' => 'New Nick',
                    'username' => 'newuser',
                    'password' => 'too-obscure',
                    'email' => 'newuser@machine.local',
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode(), $response->getBody()->getContents());

        $user = User::where('username', 'newuser')->firstOrFail();

        // Registration is performed by a guest, so the audit entry has no actor.
        $this->assertLogExists('user.nickname_changed', [
            'user_id' => $user->id,
            'old_nickname' => null,
            'new_nickname' => 'New Nick',
        ], null);
    }
}
