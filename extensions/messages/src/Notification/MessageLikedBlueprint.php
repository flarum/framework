<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Notification;

use Flarum\Database\AbstractModel;
use Flarum\Messages\DialogMessage;
use Flarum\Notification\AlertableInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\User\User;

/**
 * Someone liked a member's message, with flarum/likes enabled. An alert only:
 * a like is never worth an email.
 */
class MessageLikedBlueprint implements BlueprintInterface, AlertableInterface
{
    public function __construct(
        public DialogMessage $message,
        public User $user
    ) {
    }

    public function getSubject(): ?AbstractModel
    {
        return $this->message;
    }

    public function getFromUser(): ?User
    {
        return $this->user;
    }

    public function getData(): mixed
    {
        return null;
    }

    public static function getType(): string
    {
        return 'messageLiked';
    }

    public static function getSubjectModel(): string
    {
        return DialogMessage::class;
    }
}
