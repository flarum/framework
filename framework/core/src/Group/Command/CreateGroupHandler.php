<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Group\Command;

use Flarum\Foundation\DispatchEventsTrait;
use Flarum\Group\Event\Saving;
use Flarum\Group\Group;
use Flarum\Group\GroupValidator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;

class CreateGroupHandler
{
    use DispatchEventsTrait;

    public function __construct(
        protected Dispatcher $events,
        protected GroupValidator $validator
    ) {
    }

    public function handle(CreateGroup $command): Group
    {
        $actor = $command->actor;
        $data = $command->data;

        $actor->assertRegistered();
        $actor->assertCan('createGroup');

        $group = Group::build(
            Arr::get($data, 'attributes.nameSingular'),
            Arr::get($data, 'attributes.namePlural'),
            Arr::get($data, 'attributes.color'),
            Arr::get($data, 'attributes.icon'),
            Arr::get($data, 'attributes.isHidden', false)
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
