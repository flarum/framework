<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace integration\api\users;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class DeleteTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'ken', 'is_email_confirmed' => 1],
            ],
            'group_user' => [
                ['group_id' => 3, 'user_id' => 2],
                ['group_id' => 3, 'user_id' => 3],
            ]
        ]);
    }

    #[Test]
    #[DataProvider('authorizedUsersProvider')]
    public function can_delete_user(int $actorId, int $userId)
    {
        $this->database()->table('group_permission')->insert([
            'permission' => 'user.delete',
            'group_id' => 3,
        ]);

        $response = $this->send(
            $this->request('DELETE', "/api/users/$userId", [
                'authenticatedAs' => $actorId,
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertNull(User::find($userId));
    }

    public static function authorizedUsersProvider()
    {
        return [
            'admin can delete user' => [1, 2],
            'user with permission can delete self' => [2, 2],
            'user with permission can delete other users' => [2, 3],
        ];
    }

    #[Test]
    #[DataProvider('unauthorizedUsersProvider')]
    public function cannot_delete_user(int $actorId, int $userId)
    {
        $response = $this->send(
            $this->request('DELETE', "/api/users/$userId", [
                'authenticatedAs' => $actorId,
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertNotNull(User::find($userId));
    }

    public static function unauthorizedUsersProvider()
    {
        return [
            'user without permission cannot delete self' => [2, 2],
            'user without permission cannot delete other users' => [2, 3],
        ];
    }
}
