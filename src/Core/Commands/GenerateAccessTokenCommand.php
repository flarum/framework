<?php namespace Flarum\Core\Commands;

class GenerateAccessTokenCommand
{
    public $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }
}
