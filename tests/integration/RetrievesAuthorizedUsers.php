<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\integration;

use Flarum\User\User;

trait RetrievesAuthorizedUsers
{
    protected $userAttributes = [
        'username' => 'normal',
        'password' => 'too-obscure',
        'email' => 'normal@machine.local'
    ];

    public function getAdminUser(): User
    {
        return User::find(1);
    }

    public function getNormalUser(): User
    {
        return User::unguarded(function () {
            return User::firstOrCreate([
                'username' => $this->userAttributes['username']
            ], $this->userAttributes);
        });
    }
}
