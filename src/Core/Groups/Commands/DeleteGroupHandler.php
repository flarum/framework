<?php namespace Flarum\Core\Groups\Commands;

use Flarum\Core\Groups\Group;
use Flarum\Core\Groups\GroupRepository;
use Flarum\Events\GroupWillBeDeleted;
use Flarum\Core\Support\DispatchesEvents;

class DeleteGroupHandler
{
    use DispatchesEvents;

    /**
     * @var GroupRepository
     */
    protected $groups;

    /**
     * @param GroupRepository $groups
     */
    public function __construct(GroupRepository $groups)
    {
        $this->groups = $groups;
    }

    /**
     * @param DeleteGroup $command
     * @return Group
     * @throws \Flarum\Core\Exceptions\PermissionDeniedException
     */
    public function handle(DeleteGroup $command)
    {
        $actor = $command->actor;

        $group = $this->groups->findOrFail($command->groupId, $actor);

        $group->assertCan($actor, 'delete');

        event(new GroupWillBeDeleted($group, $actor, $command->data));

        $group->delete();
        $this->dispatchEventsFor($group);

        return $group;
    }
}
