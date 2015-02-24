<?php namespace Flarum\Core\Commands;

class RegisterUserCommand
{
    public $forum;

    public $user;

    public $username;

    public $email;

    public $password;

    public function __construct($username, $email, $password, $user, $forum)
    {
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
        $this->user = $user;
        $this->forum = $forum;
    }
}
