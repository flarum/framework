<?php namespace Flarum\Core\Users;

class UsernameValidator
{
    public function validate($username)
    {
        $illegalCharacters = '@';
        return true;
    }
}
