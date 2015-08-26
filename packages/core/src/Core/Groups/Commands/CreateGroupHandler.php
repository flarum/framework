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
use Flarum\Core\Forum;
use Flarum\Events\GroupWillBeSaved;
use Flarum\Core\Support\DispatchesEvents;

class CreateGroupHandler
{
    use DispatchesEvents;

    /**
     * @var Forum
     */
    protected $forum;

    /**
     * @param Forum $forum
     */
    public function __construct(Forum $forum)
    {
        $this->forum = $forum;
    }

    /**
     * @param CreateGroup $command
     * @return Group
     */
    public function handle(CreateGroup $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $this->forum->assertCan($actor, 'createGroup');

        $group = Group::build(
            array_get($data, 'attributes.nameSingular'),
            array_get($data, 'attributes.namePlural'),
            array_get($data, 'attributes.color'),
            array_get($data, 'attributes.icon')
        );

        event(new GroupWillBeSaved($group, $actor, $data));

        $group->save();
        $this->dispatchEventsFor($group);

        return $group;
    }
}
