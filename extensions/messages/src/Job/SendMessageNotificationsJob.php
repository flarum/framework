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
    }

    public function handle(NotificationSyncer $notifications): void
    {
        $users = User::query()
            ->whereIn('id', function (Builder $query) {
                $query->select('dialog_user.user_id')
                    ->from('dialog_user')
                    ->where('dialog_user.dialog_id', $this->message->dialog_id);
            })
            ->where('id', '!=', $this->message->user_id)
            ->get()
            ->all();

        $notifications->sync(new MessageReceivedBlueprint($this->message), $users);
    }
}
