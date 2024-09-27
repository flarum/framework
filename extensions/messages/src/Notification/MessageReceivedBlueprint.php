<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Notification;

use Flarum\Database\AbstractModel;
use Flarum\Locale\TranslatorInterface;
use Flarum\Messages\DialogMessage;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\MailableInterface;
use Flarum\User\User;

class MessageReceivedBlueprint implements BlueprintInterface, MailableInterface
{
    public function __construct(
        public DialogMessage $message
    ) {
    }

    public function getFromUser(): ?User
    {
        return $this->message->user;
    }

    public function getSubject(): ?AbstractModel
    {
        return $this->message;
    }

    public function getData(): array
    {
        return [];
    }

    public function getEmailViews(): array
    {
        return [
            'text' => 'flarum-messages::emails.plain.messageReceived',
            'html' => 'flarum-messages::emails.html.messageReceived'
        ];
    }

    public function getEmailSubject(TranslatorInterface $translator): string
    {
        return $translator->trans('flarum-messages.email.message_received.subject', [
            '{user_display_name}' => $this->message->user->display_name,
        ]);
    }

    public static function getType(): string
    {
        return 'messageReceived';
    }

    public static function getSubjectModel(): string
    {
        return DialogMessage::class;
    }
}
