<?php namespace Flarum\Core\Users\Commands;

class DeleteUserCommand
{
    public $userId;

    public $user;

    public function __construct($userId, $user)
    {
        $this->userId = $userId;
        $this->user = $user;
    }
}
