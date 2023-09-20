<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend\Tests\integration\api\users;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Laminas\Diactoros\UploadedFile;
use Psr\Http\Message\ResponseInterface;

class UploadAvatarTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
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
     * @dataProvider allowedToUploadAvatar
     * @test
     */
    public function can_suspend_user_if_allowed(?int $authenticatedAs, int $targetUserId, string $message)
    {
        $response = $this->sendUploadAvatarRequest($authenticatedAs, $targetUserId);

        $this->assertEquals(200, $response->getStatusCode(), $response->getBody()->getContents());
    }

    /**
     * @dataProvider unallowedToUploadAvatar
     * @test
     */
    public function cannot_suspend_user_if_not_allowed(?int $authenticatedAs, int $targetUserId, string $message)
    {
        $response = $this->sendUploadAvatarRequest($authenticatedAs, $targetUserId);

        $this->assertEquals(403, $response->getStatusCode(), $response->getBody()->getContents());
    }

    public function allowedToUploadAvatar(): array
    {
        return [
            [1, 2, 'Admin can upload avatar for any user'],
            [2, 3, 'User with permission can upload avatar for suspended user'],
            [2, 2, 'User with permission can upload avatar for self'],
            [2, 4, 'User with permission can upload avatar for other user'],
            [1, 1, 'Admin can upload avatar for self'],
            [5, 5, 'Suspended user can upload avatar for self if suspension expired'],
        ];
    }

    public function unallowedToUploadAvatar(): array
    {
        return [
            [3, 3, 'Suspended user cannot upload avatar for self'],
            [3, 2, 'Suspended user cannot upload avatar for other user'],
            [4, 3, 'User without permission cannot upload avatar for suspended user'],
            [4, 2, 'User without permission cannot upload avatar for other user'],
            [5, 2, 'Suspended user cannot upload avatar for other user if suspension expired'],
        ];
    }

    protected function sendUploadAvatarRequest(?int $authenticatedAs, int $targetUserId): ResponseInterface
    {
        return $this->send(
            $this->request('POST', "/api/users/$targetUserId/avatar", [
                'authenticatedAs' => $authenticatedAs,
            ])->withHeader('Content-Type', 'multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW')->withUploadedFiles([
                'avatar' => new UploadedFile(__DIR__ . '/../../../fixtures/avatar.png', 0, UPLOAD_ERR_OK, 'avatar.png', 'image/png')
            ])
        );
    }
}
