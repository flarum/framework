<?php namespace Flarum\Core\Commands;

class EditUserCommand
{
    public $userId;

    public $user;

    public $username;

    public $email;

    public $password;

    public $bio;

    public $readTime;

    public $preferences;

    public function __construct($userId, $user)
    {
        $this->userId = $userId;
        $this->user = $user;
    }
}
