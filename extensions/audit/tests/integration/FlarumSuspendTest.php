<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

use Carbon\Carbon;

class FlarumSuspendTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-suspend');

        $this->prepareDatabase([
            'users' => [
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

    /**
     * @test
     */
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

    /**
     * @test
     */
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
