<?php namespace Flarum\Core\Commands;

use RuntimeException;

class DeleteAvatarCommand
{
    public $userId;

    public $actor;

    public function __construct($userId, $actor)
    {
        if (empty($userId) || !intval($userId)) {
            throw new RuntimeException('No valid user ID specified.');
        }

        $this->userId = $userId;
        $this->actor = $actor;
    }
}
