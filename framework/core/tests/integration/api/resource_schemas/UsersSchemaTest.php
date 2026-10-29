<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\resource_schemas;

use Flarum\Group\Group;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * The users descriptor, which core describes by default (no extender opt-in).
 */
class UsersSchemaTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'searcher', 'email' => 'searcher@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'deleter', 'email' => 'deleter@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => 40, 'name_singular' => 'Searcher', 'name_plural' => 'Searchers'],
                ['id' => 41, 'name_singular' => 'Deleter', 'name_plural' => 'Deleters'],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 40],
                ['user_id' => 4, 'group_id' => 41],
            ],
            'group_permission' => [
                ['permission' => 'searchUsers', 'group_id' => 40],
                ['permission' => 'searchUsers', 'group_id' => 41],
                ['permission' => 'user.delete', 'group_id' => 41],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function describe(int $actorId): array
    {
        $response = $this->send(
            $this->request('GET', '/api/resource-schemas/users', ['authenticatedAs' => $actorId])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        return json_decode($body, true)['data']['attributes'];
    }

    /**
     * @param array<string, mixed> $descriptor
     * @return array<string, array<string, mixed>>
     */
    protected function fieldsByName(array $descriptor): array
    {
        return array_column($descriptor['fields'], null, 'name');
    }

    #[Test]
    public function admin_gets_the_type_level_descriptor()
    {
        $descriptor = $this->describe(1);

        $this->assertSame(1, $descriptor['schemaVersion']);
        $this->assertSame(['username', 'commentCount', 'discussionCount', 'lastSeenAt', 'joinedAt'], $descriptor['sorts']);
        $this->assertTrue($descriptor['search']);
        $this->assertSame(['defaultLimit' => 20, 'maxLimit' => 50], $descriptor['pagination']);
        $this->assertSame(['list' => true, 'create' => true, 'delete' => true], $descriptor['capabilities']);

        $filterKeys = array_column($descriptor['filters'], 'key');
        $this->assertContains('email', $filterKeys);
        $this->assertContains('group', $filterKeys);
    }

    #[Test]
    public function admin_gets_username_constraints()
    {
        $username = $this->fieldsByName($this->describe(1))['username'];

        $this->assertSame(3, $username['constraints']['minLength']);
        $this->assertSame(30, $username['constraints']['maxLength']);
        $this->assertSame('^(?![0-9]*$)[a-z0-9_-]+$', $username['constraints']['pattern']);
        $this->assertSame('i', $username['constraints']['patternFlags']);
    }

    #[Test]
    public function password_is_write_only_on_create()
    {
        $password = $this->fieldsByName($this->describe(1))['password'];

        $this->assertFalse($password['visible']);
        $this->assertTrue($password['writable']['create']);
    }

    #[Test]
    public function groups_is_an_updatable_to_many_relationship()
    {
        $groups = $this->fieldsByName($this->describe(1))['groups'];

        $this->assertSame('toMany', $groups['kind']);
        $this->assertSame(['groups'], $groups['relationship']['types']);
        $this->assertTrue($groups['writable']['update']);
        $this->assertSame(['column' => 70, 'display' => 'nameSingular'], $groups['ui']);
    }

    #[Test]
    public function column_fields_carry_hints_and_translated_labels()
    {
        $fields = $this->fieldsByName($this->describe(1));

        $this->assertSame(90, $fields['username']['ui']['column']);
        $this->assertSame(85, $fields['displayName']['ui']['column']);
        $this->assertSame(80, $fields['joinTime']['ui']['column']);
        $this->assertSame(70, $fields['groups']['ui']['column']);

        $this->assertSame('Username', $fields['username']['label']);
        $this->assertSame('Display name', $fields['displayName']['label']);
        $this->assertSame('Joined', $fields['joinTime']['label']);
        $this->assertSame('Groups', $fields['groups']['label']);
    }

    #[Test]
    public function self_only_fields_are_not_described()
    {
        $fields = $this->fieldsByName($this->describe(1));

        foreach (['unreadNotificationCount', 'newNotificationCount', 'preferences', 'markedAllAsReadAt', 'isAdmin'] as $name) {
            $this->assertArrayNotHasKey($name, $fields, "Self-only field [$name] leaked into the descriptor.");
        }
    }

    #[Test]
    public function a_user_who_can_only_search_users_gets_no_credentials_or_group_writes()
    {
        // With sign-up open, anyone may create an account (and so write its
        // email); close it so the actor's own permissions are what is tested.
        $this->app()->getContainer()->make(SettingsRepositoryInterface::class)->set('allow_sign_up', false);

        $descriptor = $this->describe(3);
        $fields = $this->fieldsByName($descriptor);

        $this->assertTrue($descriptor['capabilities']['list']);
        $this->assertFalse($descriptor['capabilities']['create']);

        $this->assertArrayNotHasKey('email', $fields);
        $this->assertArrayHasKey('groups', $fields);
        $this->assertArrayNotHasKey('writable', $fields['groups']);
    }

    #[Test]
    public function credentials_are_required_on_create_when_the_token_is_a_hidden_widget()
    {
        $fields = $this->fieldsByName($this->describe(1));

        // `requiredOnCreateWithout(['token'])`: the only alternative is hidden from forms.
        foreach (['username', 'email', 'password'] as $name) {
            $this->assertSame(['create' => true], $fields[$name]['required'] ?? null, "[$name] is not required on create.");
        }
    }

    #[Test]
    public function the_registration_token_is_a_hidden_widget()
    {
        $token = $this->fieldsByName($this->describe(1))['token'];

        $this->assertSame(['widget' => 'hidden'], $token['ui']);
        $this->assertFalse($token['visible']);
        $this->assertSame(['create' => true], $token['writable']);
    }

    #[Test]
    public function a_member_with_the_delete_permission_gets_the_delete_capability()
    {
        $this->assertTrue($this->describe(4)['capabilities']['delete'] ?? false);
    }

    #[Test]
    public function a_default_member_gets_a_member_scoped_descriptor()
    {
        // Default install: sign-up open, members hold `searchUsers` (spec D2).
        $descriptor = $this->describe(2);
        $fields = $this->fieldsByName($descriptor);

        // Members lack `user.delete`, so `delete` is omitted (false is the default).
        $this->assertSame(['list' => true, 'create' => true], $descriptor['capabilities']);
        // `lastSeenAt` needs `user.viewLastSeenAt` (moderators by default).
        $this->assertSame(['username', 'commentCount', 'discussionCount', 'joinedAt'], $descriptor['sorts']);
        // The email filter needs `user.editCredentials`, like `EmailFilter::filter()`.
        $this->assertSame([['key' => 'group', 'kind' => 'relationship', 'related' => 'groups']], $descriptor['filters']);

        $this->assertSame([
            'username', 'email', 'password', 'token', 'displayName', 'avatarUrl', 'avatarSrcset',
            'hasUploadedAvatar', 'slug', 'joinTime', 'discussionCount', 'commentCount', 'lastSeenAt',
            'canEdit', 'canEditAvatar', 'canEditCredentials', 'canEditGroups', 'canDelete', 'groups',
        ], array_keys($fields));

        // With sign-up open a member may create an account, so `email` is
        // described as a create-only input; it is never readable without
        // `editCredentials` (the email filter is absent above).
        $this->assertFalse($fields['email']['visible']);
        $this->assertSame(['create' => true], $fields['email']['writable']);
        $this->assertSame(['create' => true], $fields['username']['writable']);
        $this->assertArrayNotHasKey('writable', $fields['groups']);
    }
}
