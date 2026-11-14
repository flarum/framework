<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\groups;

use Flarum\Group\Group;
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
                ['id' => 3, 'username' => 'groupie', 'email' => 'groupie@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => 100, 'name_singular' => 'Tester', 'name_plural' => 'Testers'],
                ['id' => 101, 'name_singular' => 'Group admin', 'name_plural' => 'Group admins'],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 101],
            ],
            'group_permission' => [
                ['group_id' => 101, 'permission' => 'group.delete'],
            ],
        ]);
    }

    protected function delete(int $groupId, int $actorId)
    {
        return $this->send(
            $this->request('DELETE', "/api/groups/$groupId", ['authenticatedAs' => $actorId])
        );
    }

    #[Test]
    public function admin_can_delete_a_custom_group()
    {
        $response = $this->delete(100, 1);

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNull(Group::find(100));
    }

    #[Test]
    public function admin_can_delete_the_moderator_group()
    {
        $response = $this->delete(Group::MODERATOR_ID, 1);

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNull(Group::find(Group::MODERATOR_ID));
    }

    public static function builtInGroups(): array
    {
        return [
            'admin' => [Group::ADMINISTRATOR_ID],
            'guest' => [Group::GUEST_ID],
            'member' => [Group::MEMBER_ID],
        ];
    }

    #[Test]
    #[DataProvider('builtInGroups')]
    public function nobody_can_delete_a_built_in_group(int $groupId)
    {
        foreach ([1, 3] as $actorId) {
            $response = $this->delete($groupId, $actorId);

            $this->assertEquals(403, $response->getStatusCode(), "actor $actorId: ".$response->getBody());
        }

        $this->assertNotNull(Group::find($groupId));
    }

    #[Test]
    public function a_user_with_the_permission_can_delete_a_custom_group()
    {
        $response = $this->delete(100, 3);

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function a_member_without_the_permission_cannot_delete_a_group()
    {
        $response = $this->delete(100, 2);

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNotNull(Group::find(100));
    }
}
