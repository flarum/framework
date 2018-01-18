<?php

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
