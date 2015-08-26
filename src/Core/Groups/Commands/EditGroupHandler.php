<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Groups\Commands;

use Flarum\Core\Groups\Group;
use Flarum\Core\Groups\GroupRepository;
use Flarum\Events\GroupWillBeSaved;
use Flarum\Core\Support\DispatchesEvents;

class EditGroupHandler
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
     * @param EditGroup $command
     * @return Group
     * @throws \Flarum\Core\Exceptions\PermissionDeniedException
     */
    public function handle(EditGroup $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $group = $this->groups->findOrFail($command->groupId, $actor);

        $group->assertCan($actor, 'edit');

        $attributes = array_get($data, 'attributes', []);

        if (isset($attributes['nameSingular']) && isset($attributes['namePlural'])) {
            $group->rename($attributes['nameSingular'], $attributes['namePlural']);
        }

        if (isset($attributes['color'])) {
            $group->color = $attributes['color'];
        }

        if (isset($attributes['icon'])) {
            $group->icon = $attributes['icon'];
        }

        event(new GroupWillBeSaved($group, $actor, $data));

        $group->save();
        $this->dispatchEventsFor($group);

        return $group;
    }
}
