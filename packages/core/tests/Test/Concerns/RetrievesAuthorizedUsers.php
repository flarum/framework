<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\Test\Concerns;

use Flarum\User\User;

trait RetrievesAuthorizedUsers
{
    protected $userAttributes = [
        'username' => 'normal',
        'password' => 'too-obscure',
        'email' => 'normal@machine.local'
    ];

    public function getAdminUser()
    {
        return User::find(1);
    }

    public function getNormalUser()
    {
        User::unguard();

        return User::firstOrCreate([
            'username' => $this->userAttributes['username']
        ], $this->userAttributes);
    }
}
