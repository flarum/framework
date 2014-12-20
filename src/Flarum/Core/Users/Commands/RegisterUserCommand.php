<?php namespace Flarum\Core\Users\Commands;

class RegisterUserCommand
{
    public $user;

    public $username;

    public $email;

    public $password;

    public function __construct($username, $email, $password, $user)
    {
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
        $this->user = $user;
    }
}
