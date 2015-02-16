<?php namespace Flarum\Core\Users\Commands;

class ConfirmEmailCommand
{
    public $userId;

    public $token;

    public function __construct($userId, $token)
    {
        $this->userId = $userId;
        $this->token = $token;
    }
}
