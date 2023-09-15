<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Command;

use Flarum\Discussion\DiscussionRepository;
use Flarum\Discussion\Event\UserDataSaving;
use Flarum\Discussion\UserState;
use Flarum\Foundation\DispatchEventsTrait;
use Illuminate\Contracts\Events\Dispatcher;

class ReadDiscussionHandler
{
    use DispatchEventsTrait;

    public function __construct(
        protected Dispatcher $events,
        protected DiscussionRepository $discussions
    ) {
    }

    public function handle(ReadDiscussion $command): UserState
    {
        $actor = $command->actor;

        $actor->assertRegistered();

        $discussion = $this->discussions->findOrFail($command->discussionId, $actor);

        $state = $discussion->stateFor($actor);
        $state->read($command->lastReadPostNumber);

        $this->events->dispatch(
            new UserDataSaving($state)
        );

        $state->save();

        $this->dispatchEventsFor($state);

        return $state;
    }
}
