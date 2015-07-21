<?php namespace Flarum\Events;

use Flarum\Core\Users\User;

class RegisterUserPreferences
{
    public function register($key, callable $transformer = null, $default = null)
    {
        User::addPreference($key, $transformer, $default);
    }
}
