<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;
use Flarum\User\DisplayName\DriverInterface;
use Flarum\User\User;
use Illuminate\Support\Arr;

class UserTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function prepDb()
    {
        $this->prepareDatabase([
            'users' => [
                $this->adminUser(),
                $this->normalUser(),
            ],
            'group_permission' => [
                ['permission' => 'viewUserList', 'group_id' => 3]
            ],
            'settings' => [
                ['key' => 'display_name_driver', 'value' => 'custom'],
            ],
            'group_permission' => [
                ['permission' => 'viewUserList', 'group_id' => 3],
            ]
        ]);
    }

    protected function registerTestPreference()
    {
        $this->extend(
            (new Extend\User())
                ->registerPreference('test', 'boolval', true)
        );
    }

    /**
     * @test
     */
    public function username_display_name_driver_used_by_default()
    {
        $this->prepDb();

        $user = User::find(1);

        $this->assertEquals('admin', $user->displayName);
    }

    /**
     * @test
     */
    public function can_use_custom_display_name_driver()
    {
        $this->extend(
            (new Extend\User)
                ->displayNameDriver('custom', CustomDisplayNameDriver::class)
        );

        $this->prepDb();

        $user = User::find(1);

        $this->assertEquals('admin@machine.local$$$suffix', $user->displayName);
    }

    /**
     * @test
     */
    public function user_has_permissions_for_expected_groups_if_no_processors_added()
    {
        $this->prepDb();
        $user = User::find(2);

        $this->assertContains('viewUserList', $user->getPermissions());
    }

    /**
     * @test
     */
    public function processor_can_restrict_user_groups()
    {
        $this->extend((new Extend\User)->permissionGroups(function (User $user, array $groupIds) {
            return array_filter($groupIds, function ($id) {
                return $id != 3;
            });
        }));

        $this->prepDb();
        $user = User::find(2);

        $this->assertNotContains('viewUserList', $user->getPermissions());
    }

    /**
     * @test
     */
    public function processor_can_be_invokable_class()
    {
        $this->extend((new Extend\User)->permissionGroups(CustomGroupProcessorClass::class));

        $this->prepDb();
        $user = User::find(2);

        $this->assertNotContains('viewUserList', $user->getPermissions());
    }

    /**
     * @test
     */
    public function can_add_user_preference()
    {
        $this->registerTestPreference();
        $this->prepDb();

        /** @var User $user */
        $user = User::find(2);
        $this->assertEquals(true, Arr::get($user->preferences, 'test'));
    }

    /**
     * @test
     */
    public function can_store_user_preference()
    {
        $this->registerTestPreference();
        $this->prepDb();

        /** @var User $user */
        $user = User::find(2);

        $user->setPreference('test', false);

        $this->assertEquals(false, $user->getPreference('test'));
    }

    /**
     * @test
     */
    public function storing_user_preference_modified_by_transformer()
    {
        $this->registerTestPreference();
        $this->prepDb();

        /** @var User $user */
        $user = User::find(2);

        $user->setPreference('test', []);

        $this->assertEquals(false, $user->getPreference('test'));
    }
}

class CustomDisplayNameDriver implements DriverInterface
{
    public function displayName(User $user): string
    {
        return $user->email.'$$$suffix';
    }
}

class CustomGroupProcessorClass
{
    public function __invoke(User $user, array $groupIds)
    {
        return array_filter($groupIds, function ($id) {
            return $id != 3;
        });
    }
}
