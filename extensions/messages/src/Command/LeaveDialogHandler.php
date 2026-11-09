<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Command;

use Carbon\Carbon;
use Flarum\Messages\Dialog;
use Flarum\Messages\Dialog\Event\UserLeft;
use Flarum\Messages\DialogEventMessages;
use Flarum\Messages\DialogMessage;
use Flarum\Messages\UserDialogState;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;

class LeaveDialogHandler
{
    public function __construct(
        protected Dispatcher $events,
        protected ConnectionInterface $db,
        protected DialogEventMessages $eventMessages,
    ) {
    }

    public function handle(LeaveDialog $command): void
    {
        $actor = $command->actor;

        /** @var Dialog $dialog */
        $dialog = Dialog::whereVisibleTo($actor)->findOrFail($command->dialogId);

        $actor->assertCan('leave', $dialog);

        // What they wrote stays for the others. A group nobody is left in
        // goes, with its messages: there is no one left to read them.
        $notice = $this->db->transaction(function () use ($dialog, $actor): ?DialogMessage {
            // Locked first: two members leaving at once would otherwise each
            // still see the other, and neither take the emptied group away.
            Dialog::query()->whereKey($dialog->id)->lockForUpdate()->first();

            if ($dialog->groups()->exists()) {
                $state = UserDialogState::query()->firstOrCreate(
                    ['dialog_id' => $dialog->id, 'user_id' => $actor->id],
                    ['joined_at' => Carbon::now(), 'is_recipient' => false]
                );
                $state->is_recipient = false;
                $state->left_at = Carbon::now();
                $state->save();
            } else {
                $dialog->users()->detach($actor->id);
            }

            // Those still in it are told who left, in its stream.
            if ($dialog->users()->exists() || $dialog->groups()->exists()) {
                return $this->eventMessages->post($dialog, $actor, DialogMessage::USER_LEFT);
            }

            // Nobody is: the group goes, with its messages. Not left to the
            // foreign key's cascade, which SQLite only honours when told to.
            // In one query: each message's own delete hook would repair the
            // pointers of a dialog going too.
            $dialog->messages()->delete();
            $dialog->delete();

            return null;
        });

        if ($notice) {
            $this->events->dispatch(new DialogMessage\Event\Created($notice));
        }

        $this->events->dispatch(new UserLeft($dialog, $actor));
    }
}
