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

class FoFUsernameRequestTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->extension('fof-username-request', 'flarum-nicknames');

        $this->prepareDatabase([
            User::class => [
                [
                    'id' => 3,
                    'username' => 'user3',
                    'email' => 'user3@example.com',
                ],
            ],
            'username_requests' => [
                ['id' => 1, 'user_id' => 3, 'requested_username' => 'user33', 'status' => 'Sent'],
                ['id' => 2, 'user_id' => 3, 'requested_username' => 'user33', 'status' => 'Sent', 'for_nickname' => true],
            ],
        ]);
    }

    #[Test]
    public function createUsername()
    {
        $this->sendSuccessfulRequest('POST', '/api/username-requests', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'username' => 'admin2',
                    ],
                ],
                'meta' => [
                    'password' => 'password',
                ],
            ],
        ], 201);

        $this->assertLogExists('user.username_requested', [
            'user_id' => 1,
            'new_username' => 'admin2',
        ]);
    }

    #[Test]
    public function createNickname()
    {
        $this->sendSuccessfulRequest('POST', '/api/username-requests', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'username' => 'admin2',
                        'forNickname' => true,
                    ],
                ],
                'meta' => [
                    'password' => 'password',
                ],
            ],
        ], 201);

        $this->assertLogExists('user.nickname_requested', [
            'user_id' => 1,
            'new_nickname' => 'admin2',
        ]);
    }

    #[Test]
    public function approveUsername()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/username-requests/1', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'action' => 'Approved',
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('user.username_request_approved', [
            'user_id' => 3,
            'old_username' => 'user3',
            'new_username' => 'user33',
        ]);

        $this->assertLogDoesntExist('user.username_changed');
    }

    #[Test]
    public function approveNickname()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/username-requests/2', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'action' => 'Approved',
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('user.nickname_request_approved', [
            'user_id' => 3,
            'old_nickname' => null,
            'new_nickname' => 'user33',
        ]);

        $this->assertLogDoesntExist('user.nickname_changed');
    }

    #[Test]
    public function rejectUsername()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/username-requests/1', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'action' => 'Rejected',
                        'reason' => 'because',
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('user.username_request_rejected', [
            'user_id' => 3,
            'new_username' => 'user33',
            'reason' => 'because',
        ]);
    }

    #[Test]
    public function rejectNickname()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/username-requests/2', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'action' => 'Rejected',
                        'reason' => 'because',
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('user.nickname_request_rejected', [
            'user_id' => 3,
            'new_nickname' => 'user33',
            'reason' => 'because',
        ]);
    }
}
