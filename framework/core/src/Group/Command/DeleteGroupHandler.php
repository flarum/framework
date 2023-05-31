<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Group\Command;

use Flarum\Foundation\DispatchEventsTrait;
use Flarum\Group\Event\Deleting;
use Flarum\Group\Group;
use Flarum\Group\GroupRepository;
use Illuminate\Contracts\Events\Dispatcher;

class DeleteGroupHandler
{
    use DispatchEventsTrait;

    public function __construct(
        protected Dispatcher $events,
        protected GroupRepository $groups
    ) {
    }

    public function handle(DeleteGroup $command): Group
    {
        $actor = $command->actor;

        $group = $this->groups->findOrFail($command->groupId, $actor);

        $actor->assertCan('delete', $group);

        $this->events->dispatch(
            new Deleting($group, $actor, $command->data)
        );

        $group->delete();

        $this->dispatchEventsFor($group, $actor);

        return $group;
    }
}
