<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Group\Command;

use Flarum\Foundation\DispatchEventsTrait;
use Flarum\Group\Event\Saving;
use Flarum\Group\Group;
use Flarum\Group\GroupValidator;
use Flarum\User\AssertPermissionTrait;
use Illuminate\Contracts\Events\Dispatcher;

class CreateGroupHandler
{
    use DispatchEventsTrait;
    use AssertPermissionTrait;

    /**
     * @var \Flarum\Group\GroupValidator
     */
    protected $validator;

    /**
     * @param Dispatcher $events
     * @param \Flarum\Group\GroupValidator $validator
     */
    public function __construct(Dispatcher $events, GroupValidator $validator)
    {
        $this->events = $events;
        $this->validator = $validator;
    }

    /**
     * @param CreateGroup $command
     * @return \Flarum\Group\Group
     * @throws \Flarum\User\Exception\PermissionDeniedException
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

        $this->events->dispatch(
            new Saving($group, $actor, $data)
        );

        $this->validator->assertValid($group->getAttributes());

        $group->save();

        $this->dispatchEventsFor($group, $actor);

        return $group;
    }
}
