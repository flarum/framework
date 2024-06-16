<?php

namespace Flarum\Suspend\Tests\integration\api\users;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Psr\Http\Message\ResponseInterface;

class RemoveAvatarTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-suspend');

        $this->prepareDatabase([
            'users' => [
                ['id' => 1, 'username' => 'Muralf', 'email' => 'muralf@machine.local', 'is_email_confirmed' => 1],
                $this->normalUser(),
                ['id' => 3, 'username' => 'acme', 'email' => 'acme@machine.local', 'is_email_confirmed' => 1, 'suspended_until' => Carbon::now()->addDay(), 'suspend_message' => 'You have been suspended.', 'suspend_reason' => 'Suspended for acme reasons.'],
                ['id' => 4, 'username' => 'acme4', 'email' => 'acme4@machine.local', 'is_email_confirmed' => 1],
                ['id' => 5, 'username' => 'acme5', 'email' => 'acme5@machine.local', 'is_email_confirmed' => 1, 'suspended_until' => Carbon::now()->subDay(), 'suspend_message' => 'You have been suspended.', 'suspend_reason' => 'Suspended for acme reasons.'],
            ],
            'groups' => [
                ['id' => 5, 'name_singular' => 'can_edit_users', 'name_plural' => 'can_edit_users', 'is_hidden' => 0]
            ],
            'group_user' => [
                ['user_id' => 2, 'group_id' => 5]
            ],
            'group_permission' => [
                ['permission' => 'user.edit', 'group_id' => 5],
            ]
        ]);
    }

    /**
     * @test
     * @dataProvider allowedToRemoveAvatar
     */
    public function can_remove_avatar_if_allowed(?int $authenticatedAs, int $targetUserId)
    {
        $response = $this->sendRemoveAvatarRequest($authenticatedAs, $targetUserId);

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());
    }

    /**
     * @test
     * @dataProvider notAllowedToRemoveAvatar
     */
    public function cannot_remove_avatar_if_not_allowed(?int $authenticatedAs, int $targetUserId)
    {
        $response = $this->sendRemoveAvatarRequest($authenticatedAs, $targetUserId);

        $this->assertEquals(403, $response->getStatusCode(), $response->getBody()->getContents());
    }

    public function allowedToRemoveAvatar(): array
    {
        return [
            [1, 4, 'Admin can remove avatar of normal user'],
            [4, 4, 'Normal user can remove their own avatar'],
            [1, 3, 'Admin can remove avatar of suspended user'],
            [2, 3, 'Normal user with permission can remove avatar of suspended user'],
        ];
    }

    public function notAllowedToRemoveAvatar(): array
    {
        return [
            [4, 2, 'Normal user cannot remove avatar of another user'],
            [4, 3, 'Normal user cannot remove avatar of suspended user'],
            [3, 3, 'Suspended user cannot remove their own avatar'],
        ];
    }

    protected function sendRemoveAvatarRequest(?int $authenticatedAs, int $targetUserId): ResponseInterface
    {
        return $this->send(
            $this->request('DELETE', "/api/users/$targetUserId/avatar", [
                'authenticatedAs' => $authenticatedAs,
            ])
        );
    }
}
