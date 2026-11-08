<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\users;

use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * An editor who can't see hidden groups is never sent them, so the groups they
 * save for a user leave the hidden ones out. Saving must keep those groups
 * rather than remove them.
 */
class EditHiddenGroupsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /** May edit users' groups, but can't see hidden groups. */
    private const EDITOR = 3;

    private const TARGET = 4;

    private const EDITOR_GROUP = 10;

    private const HIDDEN_GROUP = 11;

    private const VISIBLE_GROUP = 12;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => self::EDITOR, 'username' => 'editor', 'email' => 'editor@machine.local', 'is_email_confirmed' => 1],
                ['id' => self::TARGET, 'username' => 'target', 'email' => 'target@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => Group::ADMINISTRATOR_ID, 'name_singular' => 'Admin', 'name_plural' => 'Admins', 'is_hidden' => 1],
                ['id' => self::EDITOR_GROUP, 'name_singular' => 'Editor', 'name_plural' => 'Editors', 'is_hidden' => 0],
                ['id' => self::HIDDEN_GROUP, 'name_singular' => 'Insider', 'name_plural' => 'Insiders', 'is_hidden' => 1],
                ['id' => self::VISIBLE_GROUP, 'name_singular' => 'Visible', 'name_plural' => 'Visibles', 'is_hidden' => 0],
            ],
            'group_user' => [
                ['user_id' => self::EDITOR, 'group_id' => self::EDITOR_GROUP],
                ['user_id' => self::TARGET, 'group_id' => self::HIDDEN_GROUP],
                ['user_id' => self::TARGET, 'group_id' => self::VISIBLE_GROUP],
            ],
            'group_permission' => [
                ['permission' => 'user.edit', 'group_id' => self::EDITOR_GROUP],
                ['permission' => 'user.editGroups', 'group_id' => self::EDITOR_GROUP],
            ],
        ]);
    }

    private function saveGroups(int $actorId, int $userId, array $groupIds)
    {
        return $this->send(
            $this->request('PATCH', "/api/users/$userId", [
                'authenticatedAs' => $actorId,
                'json' => [
                    'data' => [
                        'relationships' => [
                            'groups' => [
                                'data' => array_map(fn (int $id) => ['id' => (string) $id, 'type' => 'groups'], $groupIds),
                            ],
                        ],
                    ],
                ],
            ])
        );
    }

    private function groupIdsOf(int $userId): array
    {
        $ids = User::query()->findOrFail($userId)->groups()->pluck('id')->map(fn ($id) => (int) $id)->all();
        sort($ids);

        return $ids;
    }

    #[Test]
    public function saving_visible_groups_keeps_hidden_groups_the_editor_cant_see()
    {
        $response = $this->saveGroups(self::EDITOR, self::TARGET, [self::VISIBLE_GROUP]);

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals([self::HIDDEN_GROUP, self::VISIBLE_GROUP], $this->groupIdsOf(self::TARGET));
    }

    #[Test]
    public function removing_visible_groups_keeps_hidden_groups_the_editor_cant_see()
    {
        $response = $this->saveGroups(self::EDITOR, self::TARGET, []);

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals([self::HIDDEN_GROUP], $this->groupIdsOf(self::TARGET));
    }

    #[Test]
    public function editor_can_change_an_admins_visible_groups_when_the_admin_group_is_hidden()
    {
        $response = $this->saveGroups(self::EDITOR, 1, [self::VISIBLE_GROUP]);

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals([Group::ADMINISTRATOR_ID, self::VISIBLE_GROUP], $this->groupIdsOf(1));
    }

    #[Test]
    public function an_actor_who_can_see_hidden_groups_can_still_remove_them()
    {
        $response = $this->saveGroups(1, self::TARGET, [self::VISIBLE_GROUP]);

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals([self::VISIBLE_GROUP], $this->groupIdsOf(self::TARGET));
    }
}
