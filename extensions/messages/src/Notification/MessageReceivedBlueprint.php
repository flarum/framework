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
    /**
     * @param int|null $alsoSentTo In a group, how many besides the recipient the
     *                             message went to. Counted once, by whoever sends the
     *                             notifications, not once per email; worked out here if not.
     */
    public function __construct(
        public DialogMessage $message,
        public ?int $alsoSentTo = null,
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
        // A group everyone else has left reads as a direct message.
        if ($alsoSentTo = $this->alsoSentTo ?? self::countAlsoSentTo($this->message)) {
            return $translator->trans('flarum-messages.email.message_received.group_subject', [
                'user_display_name' => $this->message->user->display_name ?? $translator->trans('core.lib.username.deleted_text'),
                'count' => $alsoSentTo,
            ]);
        }

        return $translator->trans('flarum-messages.email.message_received.subject', [
            '{user_display_name}' => $this->message->user->display_name ?? $translator->trans('core.lib.username.deleted_text'),
        ]);
    }

    /**
     * The members of a group besides the sender and any one recipient.
     */
    public static function countAlsoSentTo(DialogMessage $message): int
    {
        if (! $message->dialog?->isGroup()) {
            return 0;
        }

        $others = $message->dialog->users()->where('users.id', '!=', $message->user_id)->count();

        return max(0, $others - 1);
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
