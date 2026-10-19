<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Job;

use Flarum\Messages\DialogMessage;
use Flarum\Messages\Notification\MessageReceivedBlueprint;
use Flarum\Notification\NotificationSyncer;
use Flarum\Queue\AbstractJob;
use Flarum\User\User;
use Illuminate\Database\Query\Builder;

class SendMessageNotificationsJob extends AbstractJob
{
    public function __construct(
        protected DialogMessage $message
    ) {
        parent::__construct();
    }

    public function handle(NotificationSyncer $notifications): void
    {
        $users = User::query()
            ->whereIn('id', function (Builder $query) {
                // Only members who had read everything before this message.
                // Anyone with something older unread has already been told
                // about this conversation, and isn't told again until they
                // have read it. Anyone who has read this message by the time
                // the job runs doesn't need telling at all.
                $query->select('dialog_user.user_id')
                    ->from('dialog_user')
                    ->where('dialog_user.dialog_id', $this->message->dialog_id)
                    ->where('dialog_user.last_read_message_id', '<', $this->message->id)
                    ->whereNotExists(function (Builder $query) {
                        $query->selectRaw('1')
                            ->from('dialog_messages')
                            ->whereColumn('dialog_messages.dialog_id', 'dialog_user.dialog_id')
                            ->whereColumn('dialog_messages.id', '>', 'dialog_user.last_read_message_id')
                            ->where('dialog_messages.id', '<', $this->message->id);
                    });
            })
            ->where('id', '!=', $this->message->user_id)
            ->get()
            ->all();

        $notifications->sync(new MessageReceivedBlueprint($this->message), $users);
    }
}
