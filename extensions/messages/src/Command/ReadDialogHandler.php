<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Command;

use Flarum\Foundation\DispatchEventsTrait;
use Flarum\Messages\Dialog;
use Flarum\Messages\Dialog\Event\UserDataSaving;
use Flarum\Messages\UserDialogState;
use Illuminate\Contracts\Events\Dispatcher;

class ReadDialogHandler
{
    use DispatchEventsTrait;

    public function __construct(
        protected Dispatcher $events,
    ) {
    }

    public function handle(ReadDialog $command): UserDialogState
    {
        $actor = $command->actor;

        $actor->assertRegistered();

        /** @var Dialog $dialog */
        $dialog = Dialog::whereVisibleTo($actor)->findOrFail($command->dialogId);

        /** @var UserDialogState $state */
        $state = $dialog->state($actor)->first();
        $state->read($command->lastReadMessageId);

        $this->events->dispatch(
            new UserDataSaving($state)
        );

        $state->save();

        $this->dispatchEventsFor($state);

        return $state;
    }
}
