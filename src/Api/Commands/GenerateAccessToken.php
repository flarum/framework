<?php namespace Flarum\Api\Commands;

class GenerateAccessToken
{
    /**
     * The ID of the user to generate an access token for.
     *
     * @var int
     */
    public $userId;

    /**
     * @param int $userId The ID of the user to generate an access token for.
     */
    public function __construct($userId)
    {
        $this->userId = $userId;
    }
}