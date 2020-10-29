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
use Flarum\User\User;

class AuthTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function prepDb()
    {
        $this->prepareDatabase([
            'users' => [
                $this->adminUser(),
            ],
        ]);
    }

    /**
     * @test
     */
    public function standard_password_works_by_default()
    {
        $this->prepDb();

        $user = User::find(1);

        $this->assertTrue($user->checkPassword('password'));
    }

    /**
     * @test
     */
    public function standard_password_can_be_disabled()
    {
        $this->extend(
            (new Extend\Auth)
                ->removePasswordChecker('standard')
        );

        $this->prepDb();

        $user = User::find(1);

        $this->assertFalse($user->checkPassword('password'));
    }

    /**
     * @test
     */
    public function custom_checker_can_be_added()
    {
        $this->extend(
            (new Extend\Auth)
                ->removePasswordChecker('standard')
                ->addPasswordChecker('custom_true', CustomTrueChecker::class)
        );

        $this->prepDb();

        $user = User::find(1);

        $this->assertTrue($user->checkPassword('DefinitelyNotThePassword'));
    }

    /**
     * @test
     */
    public function false_checker_overrides_true()
    {
        $this->extend(
            (new Extend\Auth)
                ->addPasswordChecker('custom_false', function (User $user, $password) {
                    return false;
                })
        );

        $this->prepDb();

        $user = User::find(1);

        $this->assertFalse($user->checkPassword('password'));
    }
}

class CustomTrueChecker
{
    public function __invoke(User $user, $password)
    {
        return true;
    }
}
