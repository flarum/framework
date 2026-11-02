<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\security\listing;

use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Security: hidden groups through `/api/groups` filters/sorts/negation, the
 * built-in group delete protection, and the `isHidden` writability.
 */
class GroupListingTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private const HIDDEN_GROUP = 10;
    private const EDITOR_GROUP = 11;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 5, 'username' => 'groupeditor', 'email' => 'ge@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => self::HIDDEN_GROUP, 'name_singular' => 'Secret', 'name_plural' => 'Secrets', 'is_hidden' => 1],
                ['id' => self::EDITOR_GROUP, 'name_singular' => 'Editor', 'name_plural' => 'Editors', 'is_hidden' => 0],
            ],
            'group_user' => [
                ['user_id' => 5, 'group_id' => self::EDITOR_GROUP],
            ],
            'group_permission' => [
                ['permission' => 'group.edit', 'group_id' => self::EDITOR_GROUP],
            ],
        ]);
    }

    private function ids(?int $actor, array $query): array
    {
        $response = $this->send(
            $this->request('GET', '/api/groups', $actor ? ['authenticatedAs' => $actor] : [])->withQueryParams($query)
        );
        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $ids = array_map('intval', array_column(json_decode((string) $response->getBody(), true)['data'], 'id'));
        sort($ids);

        return $ids;
    }

    public static function probes(): array
    {
        return [
            'plain' => [[]],
            'hidden' => [['filter' => ['hidden' => '1']]],
            'negated not hidden' => [['filter' => ['-hidden' => '0']]],
            'sort isHidden' => [['sort' => '-isHidden']],
            'q' => [['filter' => ['q' => 'Secret']]],
        ];
    }

    #[Test]
    #[DataProvider('probes')]
    public function guests_and_members_never_see_the_hidden_group(array $query)
    {
        foreach ([null, 2] as $actor) {
            $this->assertNotContains(self::HIDDEN_GROUP, $this->ids($actor, $query));
        }
    }

    #[Test]
    public function show_hidden_group_is_404_for_a_member()
    {
        $response = $this->send($this->request('GET', '/api/groups/'.self::HIDDEN_GROUP, ['authenticatedAs' => 2]));
        $this->assertSame(404, $response->getStatusCode());
    }

    #[Test]
    #[DataProvider('builtIn')]
    public function built_in_groups_cannot_be_deleted_even_by_admin(int $id)
    {
        $response = $this->send($this->request('DELETE', "/api/groups/$id", ['authenticatedAs' => 1]));
        $this->assertSame(403, $response->getStatusCode());
        $this->assertNotNull(Group::query()->find($id));
    }

    public static function builtIn(): array
    {
        return [[Group::ADMINISTRATOR_ID], [Group::GUEST_ID], [Group::MEMBER_ID]];
    }

    #[Test]
    public function a_member_cannot_unhide_or_rename_a_group()
    {
        foreach ([self::HIDDEN_GROUP, self::EDITOR_GROUP] as $id) {
            $response = $this->send($this->request('PATCH', "/api/groups/$id", [
                'authenticatedAs' => 2,
                'json' => ['data' => ['type' => 'groups', 'id' => (string) $id, 'attributes' => ['isHidden' => false, 'nameSingular' => 'pwn']]],
            ]));
            $this->assertContains($response->getStatusCode(), [403, 404], (string) $response->getBody());
        }
        $this->assertTrue((bool) Group::query()->find(self::HIDDEN_GROUP)->is_hidden);
    }

    #[Test]
    public function a_member_cannot_create_a_hidden_group()
    {
        $response = $this->send($this->request('POST', '/api/groups', [
            'authenticatedAs' => 2,
            'json' => ['data' => ['type' => 'groups', 'attributes' => ['nameSingular' => 'x', 'namePlural' => 'xs', 'isHidden' => true]]],
        ]));
        $this->assertSame(403, $response->getStatusCode());
    }

    #[Test]
    public function a_group_editor_without_view_hidden_cannot_reach_the_hidden_group()
    {
        $response = $this->send($this->request('PATCH', '/api/groups/'.self::HIDDEN_GROUP, [
            'authenticatedAs' => 5,
            'json' => ['data' => ['type' => 'groups', 'id' => (string) self::HIDDEN_GROUP, 'attributes' => ['isHidden' => false]]],
        ]));
        $this->assertSame(404, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function the_group_edit_permission_cannot_rename_the_admin_group_to_an_empty_name()
    {
        $response = $this->send($this->request('PATCH', '/api/groups/'.Group::ADMINISTRATOR_ID, [
            'authenticatedAs' => 5,
            'json' => ['data' => ['type' => 'groups', 'id' => '1', 'attributes' => ['nameSingular' => '', 'namePlural' => null]]],
        ]));
        $this->assertSame(422, $response->getStatusCode(), (string) $response->getBody());
    }
}
