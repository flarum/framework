<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Job;

use Carbon\Carbon;
use Flarum\Messages\DialogAudience;
use Flarum\Messages\DialogMessage;
use Flarum\Messages\Notification\MessageReceivedBlueprint;
use Flarum\Messages\UserDialogState;
use Flarum\Notification\NotificationSyncer;
use Flarum\Queue\AbstractJob;
use Flarum\User\User;
use Illuminate\Database\Query\Builder;

class SendMessageNotificationsJob extends AbstractJob
{
    /** @var int[]|null Null for jobs queued before recipient snapshots existed. */
    protected ?array $recipientIds = null;

    public function __construct(
        protected DialogMessage $message
    ) {
        parent::__construct();
        $this->recipientIds = DialogAudience::users($message->dialog)->pluck('users.id')->all();
    }

    public function handle(NotificationSyncer $notifications): void
    {
        // Deleted by its author before the queue got here: there is nothing
        // to tell anyone about.
        if ($this->message->isHidden()) {
            return;
        }

        // $marked is the member's mark-all watermark as SQL; it differs between the two queries.
        $eligible = function ($query, string $marked) {
            $query->where('dialog_user.last_read_message_id', '<', $this->message->id)
                ->whereRaw("$marked < ?", [$this->message->id])
                ->where(function ($query) use ($marked) {
                    $query->where('dialog_user.last_notified_message_id', 0)
                        ->orWhere('dialog_user.last_notified_message_id', $this->message->id)
                        // Reading only part of an unread period must not start another email.
                        ->orWhere(function ($query) use ($marked) {
                            $query->where(function ($query) use ($marked) {
                                $query->whereNull('dialog_user.last_notified_message_id')
                                    ->orWhereColumn('dialog_user.last_notified_message_id', '<=', 'dialog_user.last_read_message_id')
                                    ->orWhereRaw($query->getGrammar()->wrap('dialog_user.last_notified_message_id')." <= $marked");
                            })->whereNotExists(function (Builder $query) use ($marked) {
                                $query->selectRaw('1')->from('dialog_messages')
                                    ->where('dialog_messages.dialog_id', $this->message->dialog_id)
                                    ->where('dialog_messages.type', DialogMessage::COMMENT)
                                    ->whereColumn('dialog_messages.id', '>', 'dialog_user.last_read_message_id')
                                    ->whereRaw($query->getGrammar()->wrap('dialog_messages.id')." > $marked")
                                    ->where('dialog_messages.id', '<', $this->message->id);
                            });
                        });
                });
        };
        $grammar = UserDialogState::query()->getQuery()->getGrammar();
        $joinedMark = $grammar->wrap('users.marked_all_dialogs_read_id');
        $lookedUpMark = '(select '.$grammar->wrap('marked_users.marked_all_dialogs_read_id')
            .' from '.$grammar->wrapTable('users').' as '.$grammar->wrapTable('marked_users')
            .' where '.$grammar->wrap('marked_users.id').' = '.$grammar->wrap('dialog_user.user_id').')';
        $blueprint = new MessageReceivedBlueprint($this->message, MessageReceivedBlueprint::countAlsoSentTo($this->message));
        DialogAudience::users($this->message->dialog)
            ->select('users.*')
            ->when($this->recipientIds !== null, fn ($query) => $query->whereIntegerInRaw('users.id', $this->recipientIds))
            ->leftJoin('dialog_user', function ($join) {
                $join->on('dialog_user.user_id', '=', 'users.id')
                    ->where('dialog_user.dialog_id', $this->message->dialog_id);
            })
            ->where(fn ($query) => $query->whereNull('dialog_user.id')->orWhere(fn ($query) => $eligible($query, $joinedMark)))
            ->where('users.id', '!=', $this->message->user_id)
            ->with('groups')
            ->chunkById(500, function ($batch) use ($eligible, $lookedUpMark, $notifications, $blueprint) {
                $users = $batch
                    ->filter(fn (User $user) => ! $this->message->user || $this->message->user->can('message', $user))
                    ->all();
                if (! $users) {
                    return;
                }

                // State alone never grants individual access.
                UserDialogState::query()->getConnection()->table('dialog_user')->insertOrIgnore(array_map(
                    fn (User $user) => ['dialog_id' => $this->message->dialog_id, 'user_id' => $user->id, 'joined_at' => Carbon::now(), 'is_recipient' => false, 'last_notified_message_id' => 0],
                    $users
                ));

                $ids = array_map(fn (User $user) => $user->id, $users);
                // Claim this unread period atomically. A retry may finish its own claim.
                UserDialogState::query()->where('dialog_id', $this->message->dialog_id)
                    ->whereIntegerInRaw('user_id', $ids)->where(fn ($query) => $eligible($query, $lookedUpMark))
                    ->update(['last_notified_message_id' => $this->message->id]);
                $claimed = array_fill_keys(UserDialogState::query()->where('dialog_id', $this->message->dialog_id)
                    ->whereIntegerInRaw('user_id', $ids)->where('last_notified_message_id', $this->message->id)
                    ->pluck('user_id')->all(), true);
                $users = array_values(array_filter($users, fn (User $user) => isset($claimed[$user->id])));

                // This notification is email-only, so sync has no alert records to reconcile across batches.
                $notifications->sync($blueprint, $users);
            }, 'users.id', 'id');
    }
}
