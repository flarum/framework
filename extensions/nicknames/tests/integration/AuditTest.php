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
}
