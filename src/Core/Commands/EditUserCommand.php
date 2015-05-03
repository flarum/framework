<?php namespace Flarum\Core\Commands;

class EditUserCommand
{
    public $userId;

    public $user;

    public $data;

    public function __construct($userId, $user, $data)
    {
        $this->userId = $userId;
        $this->user = $user;
        $this->data = $data;
    }
}
