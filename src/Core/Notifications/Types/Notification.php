<?php namespace Flarum\Core\Notifications\Types;

use Flarum\Core\Models\User;

abstract class Notification
{
    protected $recipient;

    protected $sender;

    public function __construct(User $recipient, User $sender)
    {
        $this->recipient = $recipient;
        $this->sender = $sender;
    }

    public function getRecipient()
    {
        return $this->recipient;
    }

    public function getSender()
    {
        return $this->sender;
    }

    public static function getType()
    {
        return null;
    }

    public static function getSubjectModel()
    {
        return null;
    }
}
