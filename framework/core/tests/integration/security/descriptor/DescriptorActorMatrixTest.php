<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\security\descriptor;

use Flarum\Group\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

// Only test files are loaded by path; the root autoloader maps Flarum\Tests elsewhere.
require_once __DIR__.'/AbstractDescriptorSecurityTestCase.php';

/**
 * Security: what each actor learns from the core descriptors.
 */
class DescriptorActorMatrixTest extends AbstractDescriptorSecurityTestCase
{
    public static function actorsAndTypes(): array
    {
        $cases = [];

        foreach (self::ACTORS as $actor => $id) {
            foreach (['users', 'groups', 'access-tokens'] as $type) {
                $cases["$actor $type"] = [$id, $type];
            }
        }

        return $cases;
    }

    #[Test]
    #[DataProvider('actorsAndTypes')]
    public function no_descriptor_carries_database_values_settings_or_internals(int $actorId, string $type)
    {
        [, , $body] = $this->describeRaw($type, $actorId);

        $this->assertNoSecrets($body, "actor $actorId $type");
    }

    #[Test]
    #[DataProvider('actorsAndTypes')]
    public function debug_mode_adds_nothing_to_a_descriptor(int $actorId, string $type)
    {
        [, $production] = $this->describeRaw($type, $actorId);

        $this->config('debug', true);

        [, $debug, $body] = $this->describeRaw($type, $actorId);

        $this->assertSame($production, $debug);
        $this->assertNoSecrets($body, "debug actor $actorId $type");
    }

    #[Test]
    public function a_member_learns_nothing_about_credentials_beyond_the_public_sign_up_form()
    {
        $descriptor = $this->describe('users', self::ACTORS['member']);
        $fields = $this->fieldsOf($descriptor);
        $filters = $this->filtersOf($descriptor);

        // Never visible to someone else's eyes.
        foreach (['email', 'password', 'token', 'colorScheme'] as $name) {
            $this->assertFalse($fields[$name]['visible'] ?? true, "$name must be described as not visible");
            // Only the sign-up form's create-time writability, never update.
            $this->assertSame(['create' => true], $fields[$name]['writable'] ?? null, $name);
            $this->assertArrayNotHasKey('constraintsOnUpdate', $fields[$name]);
        }

        foreach (['isEmailConfirmed', 'markedAllAsReadAt', 'preferences', 'isAdmin', 'unreadNotificationCount', 'newNotificationCount'] as $name) {
            $this->assertArrayNotHasKey($name, $fields, "$name leaks to a member");
        }

        $this->assertArrayNotHasKey('email', $filters);
        $this->assertArrayNotHasKey('lastSeenAt', $filters);
        $this->assertArrayNotHasKey('update', $fields['username']['writable'] ?? []);
        $this->assertArrayNotHasKey('writable', $fields['groups']);
        $this->assertArrayNotHasKey('delete', $descriptor['capabilities']);
    }

    #[Test]
    public function a_sign_up_closed_forum_hides_even_the_sign_up_fields_from_a_member()
    {
        $this->setting('allow_sign_up', false);

        $descriptor = $this->describe('users', self::ACTORS['member']);
        $fields = $this->fieldsOf($descriptor);

        $this->assertFalse($descriptor['capabilities']['create']);

        foreach (['email', 'password', 'token', 'colorScheme'] as $name) {
            $this->assertArrayNotHasKey($name, $fields, "$name described although the member cannot create users");
        }
    }

    #[Test]
    public function an_unconfirmed_user_without_list_access_is_refused_once_sign_up_is_closed()
    {
        $this->setting('allow_sign_up', false);

        [$status] = $this->describeRaw('users', self::ACTORS['unconfirmed']);

        $this->assertSame(403, $status);
    }

    #[Test]
    public function a_moderator_gets_exactly_their_permissions()
    {
        $descriptor = $this->describe('users', self::ACTORS['moderator']);
        $fields = $this->fieldsOf($descriptor);
        $filters = $this->filtersOf($descriptor);

        // user.editGroups
        $this->assertSame(['update' => true], $fields['groups']['writable'] ?? null);

        // no user.editCredentials
        $this->assertFalse($fields['email']['visible'] ?? true);
        $this->assertArrayNotHasKey('update', $fields['email']['writable'] ?? []);
        $this->assertArrayNotHasKey('update', $fields['password']['writable'] ?? []);
        $this->assertArrayNotHasKey('isEmailConfirmed', $fields);
        $this->assertArrayNotHasKey('email', $filters);

        // no user.viewLastSeenAt
        $this->assertArrayNotHasKey('lastSeenAt', $filters);

        // no user.delete
        $this->assertArrayNotHasKey('delete', $descriptor['capabilities']);

        // no moderateAccessTokens: no filtering tokens by someone else
        $this->assertArrayNotHasKey('user', $this->filtersOf($this->describe('access-tokens', self::ACTORS['moderator'])));
    }

    #[Test]
    public function an_admin_gets_the_privileged_shape()
    {
        $descriptor = $this->describe('users', self::ACTORS['admin']);
        $fields = $this->fieldsOf($descriptor);
        $filters = $this->filtersOf($descriptor);

        $this->assertNotFalse($fields['email']['visible'] ?? true);
        $this->assertArrayHasKey('isEmailConfirmed', $fields);
        $this->assertArrayHasKey('email', $filters);
        $this->assertArrayHasKey('lastSeenAt', $filters);
        $this->assertTrue($descriptor['capabilities']['delete'] ?? false);
        $this->assertArrayHasKey('user', $this->filtersOf($this->describe('access-tokens', self::ACTORS['admin'])));
    }

    #[Test]
    public function the_raw_token_is_never_described_as_visible()
    {
        foreach (self::ACTORS as $id) {
            $fields = $this->fieldsOf($this->describe('access-tokens', $id));

            $this->assertFalse(isset($fields['token']) && ($fields['token']['visible'] ?? true), "actor $id: token described as visible");
        }
    }

    #[Test]
    public function a_member_cannot_edit_groups_through_the_descriptor()
    {
        $fields = $this->fieldsOf($this->describe('groups', self::ACTORS['member']));

        foreach ($fields as $name => $field) {
            $this->assertArrayNotHasKey('writable', $field, "$name described as writable to a member");
        }
    }

    #[Test]
    public function group_enums_are_never_built_from_group_rows()
    {
        // No field or filter enumerates groups: relations are described by type only,
        // so hidden group names cannot surface whatever the actor.
        foreach (self::ACTORS as $id) {
            foreach (['users', 'groups'] as $type) {
                $descriptor = $this->describe($type, $id);

                foreach ([...$descriptor['fields'], ...($descriptor['filters'] ?? [])] as $entry) {
                    $this->assertStringNotContainsString('SecretCabal', json_encode($entry));
                    $this->assertStringNotContainsString(Group::find(11)->name_singular, json_encode($entry));
                }
            }
        }
    }
}
