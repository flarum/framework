<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Command;

use Flarum\Discussion\Discussion;
use Flarum\Discussion\DiscussionRepository;
use Flarum\Discussion\Event\Deleting;
use Flarum\Foundation\DispatchEventsTrait;
use Illuminate\Contracts\Events\Dispatcher;

class DeleteDiscussionHandler
{
    use DispatchEventsTrait;

    public function __construct(
        protected Dispatcher $events,
        protected DiscussionRepository $discussions
    ) {
    }

    public function handle(DeleteDiscussion $command): Discussion
    {
        $actor = $command->actor;

        $discussion = $this->discussions->findOrFail($command->discussionId, $actor);

        $actor->assertCan('delete', $discussion);

        $this->events->dispatch(
            new Deleting($discussion, $actor, $command->data)
        );

        $discussion->delete();

        $this->dispatchEventsFor($discussion, $actor);

        return $discussion;
    }
}
