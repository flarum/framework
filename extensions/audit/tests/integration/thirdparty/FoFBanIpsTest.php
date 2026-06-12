<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration\thirdparty;

use Flarum\Audit\Tests\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class FoFBanIpsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-ban-ips');

        $this->prepareDatabase([
            User::class => [
                [
                    'id' => 3,
                    'username' => 'user3',
                    'email' => 'user3@example.com',
                ],
            ],
        ]);
    }

    #[Test]
    public function banned()
    {
        $this->sendSuccessfulRequest('POST', '/api/fof/ban-ips', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'address' => '192.168.2.2',
                        'reason' => 'Because',
                    ],
                ],
            ],
        ], 201);

        $this->assertLogExists('fof_ban_ips.banned', [
            'ip' => '192.168.2.2',
            'reason' => 'Because',
        ]);
    }

    #[Test]
    public function banned_user()
    {
        $this->sendSuccessfulRequest('POST', '/api/fof/ban-ips', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'userId' => 3,
                        'address' => '192.168.2.3',
                        'reason' => 'Because',
                    ],
                ],
            ],
        ], 201);

        $this->assertLogExists('fof_ban_ips.banned', [
            'ip' => '192.168.2.3',
            'reason' => 'Because',
            'user_id' => 3,
        ]);
    }

    // We can't test unbanned without user because the event is not dispatched
    // https://github.com/FriendsOfFlarum/ban-ips/issues/4

    #[Test]
    public function unbanned_user()
    {
        $this->sendSuccessfulRequest('POST', '/api/fof/ban-ips', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'userId' => 3,
                        'address' => '192.168.2.4',
                        'reason' => 'Because',
                    ],
                ],
            ],
        ], 201);

        $this->sendSuccessfulRequest('POST', '/api/users/3/unban', []);

        $this->assertLogExists('fof_ban_ips.unbanned', [
            'ip' => '192.168.2.4',
            'user_id' => 3,
        ]);
    }
}
