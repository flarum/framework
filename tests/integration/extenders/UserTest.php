<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\User\DisplayName\DriverInterface;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;
use Flarum\User\User;

class UserTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function prepDb()
    {
        $this->prepareDatabase([
            'users' => [
                $this->adminUser(),
            ], 'settings' => [
                ['key' => 'display_name_driver', 'value' => 'custom'],
            ],
        ]);
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
}

class CustomDisplayNameDriver implements DriverInterface
{
    public function displayName(User $user): string
    {
        return $user->email.'$$$suffix';
    }
}
