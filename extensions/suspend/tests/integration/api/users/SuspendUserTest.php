<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend\Tests\integration\api\users;

use Carbon\Carbon;
use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Psr\Http\Message\ResponseInterface;

class SuspendUserTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-suspend');

        $this->prepareDatabase([
            User::class => [
                ['id' => 1, 'username' => 'Muralf', 'email' => 'muralf@machine.local', 'is_email_confirmed' => 1],
                $this->normalUser(),
                ['id' => 3, 'username' => 'acme', 'email' => 'acme@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => 5, 'name_singular' => 'Acme', 'name_plural' => 'Acme', 'is_hidden' => 0]
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 5]
            ],
            'group_permission' => [
                ['permission' => 'user.suspend', 'group_id' => 5]
            ]
        ]);
    }

    /**
     * @dataProvider allowedToSuspendUser
     * @test
     */
    public function can_suspend_user_if_allowed(?int $authenticatedAs, int $targetUserId, string $message)
    {
        $response = $this->sendSuspensionRequest($authenticatedAs, $targetUserId);

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());
    }

    /**
     * @dataProvider unallowedToSuspendUser
     * @test
     */
    public function cannot_suspend_user_if_not_allowed(?int $authenticatedAs, int $targetUserId, string $message)
    {
        $response = $this->sendSuspensionRequest($authenticatedAs, $targetUserId);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function allowedToSuspendUser(): array
    {
        return [
            [1, 2, 'Admin can suspend any user'],
            [1, 3, 'Admin can suspend any user'],
            [3, 2, 'User with permission can suspend any user'],
        ];
    }

    public function unallowedToSuspendUser(): array
    {
        return [
            [1, 1, 'Admin cannot suspend self'],
            [2, 2, 'User without permission cannot suspend self'],
            [2, 3, 'User without permission cannot suspend other user'],
            [3, 3, 'User with permission cannot suspend self'],
            [3, 1, 'User with permission cannot suspend admin'],
        ];
    }

    protected function sendSuspensionRequest(?int $authenticatedAs, int $targetUserId): ResponseInterface
    {
        return $this->send(
            $this->request('PATCH', "/api/users/$targetUserId", [
                'authenticatedAs' => $authenticatedAs,
                'json' => [
                    'data' => [
                        'type' => 'users',
                        'attributes' => [
                            'suspendedUntil' => Carbon::now()->addDay(),
                            'suspendReason' => 'Suspended for acme reasons.',
                            'suspendMessage' => 'You have been suspended.',
                        ]
                    ]
                ]
            ])
        );
    }
}
