<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Command;

use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Core\Exception\PermissionDeniedException;
use Flarum\Core\Group;
use Flarum\Event\GroupWillBeSaved;
use Flarum\Core\Support\DispatchEventsTrait;
use Illuminate\Contracts\Events\Dispatcher;

class CreateGroupHandler
{
    use DispatchEventsTrait;
    use AssertPermissionTrait;

    /**
     * @param Dispatcher $events
     */
    public function __construct(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * @param CreateGroup $command
     * @return Group
     * @throws PermissionDeniedException
     */
    public function handle(CreateGroup $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $this->assertCan($actor, 'createGroup');

        $group = Group::build(
            array_get($data, 'attributes.nameSingular'),
            array_get($data, 'attributes.namePlural'),
            array_get($data, 'attributes.color'),
            array_get($data, 'attributes.icon')
        );

        $this->events->fire(
            new GroupWillBeSaved($group, $actor, $data)
        );

        $group->save();

        $this->dispatchEventsFor($group, $actor);

        return $group;
    }
}
