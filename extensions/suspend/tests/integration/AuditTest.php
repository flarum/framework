<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend\Tests\integration;

use Carbon\Carbon;
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

        $this->extension('flarum-audit', 'flarum-suspend');

        $this->prepareDatabase([
            User::class => [
                [
                    'id' => 3,
                    'username' => 'user3',
                    'email' => 'user3@example.com',
                ],
                [
                    'id' => 4,
                    'username' => 'user4',
                    'email' => 'user4@example.com',
                    'suspended_until' => Carbon::parse('2030-01-01'),
                ],
            ],
        ]);
    }

    #[Test]
    public function suspend()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/users/3', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'suspendedUntil' => '2021-02-01T12:00:00+00:00',
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('user.suspended', [
            'user_id' => 3,
            'until' => '2021-02-01T12:00:00+00:00',
        ]);
    }

    #[Test]
    public function unsuspend()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/users/4', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'suspendedUntil' => null,
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('user.unsuspended', [
            'user_id' => 4,
        ]);
    }
}
