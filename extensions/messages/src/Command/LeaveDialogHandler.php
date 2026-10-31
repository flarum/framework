<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Command;

use Flarum\Messages\Dialog;
use Flarum\Messages\Dialog\Event\UserLeft;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;

class LeaveDialogHandler
{
    public function __construct(
        protected Dispatcher $events,
        protected ConnectionInterface $db,
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
        $this->db->transaction(function () use ($dialog, $actor) {
            // Locked first: two members leaving at once would otherwise each
            // still see the other, and neither take the emptied group away.
            Dialog::query()->whereKey($dialog->id)->lockForUpdate()->first();

            $dialog->users()->detach($actor->id);

            if ($dialog->users()->doesntExist()) {
                // Not left to the foreign key's cascade, which SQLite only
                // honours when told to. In one query: each message's own
                // delete hook would repair the pointers of a dialog going too.
                $dialog->messages()->delete();
                $dialog->delete();
            }
        });

        $this->events->dispatch(new UserLeft($dialog, $actor));
    }
}
