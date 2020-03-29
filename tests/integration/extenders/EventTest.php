<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Group\Group;
use Flarum\Group\Event\Created;
use Flarum\Tests\integration\TestCase;
use Illuminate\Contracts\Events\Dispatcher;

class EventTest extends TestCase
{
    protected function buildGroup() {
        $events = $this->app()->getContainer()->make(Dispatcher::class);

        $group = Group::build('test group', 'test groups', '#000000', 'fas fa-crown');
        $group->save();

        $events->dispatch(new Created($group));

        return $group;
    }
    /**
     * @test
     */
    public function custom_listener_doesnt_work_by_default()
    {
        $group = $this->buildGroup();

        $this->assertEquals($group->name_singular, 'test group');
    }

    /**
     * @test
     */
    public function custom_listener_works_with_closure()
    {
        $this->extend((new Extend\Event)->listen(Created::class, function (Created $event) {
            $event->group->name_singular = 'modified group';
        }));

        $group = $this->buildGroup();

        $this->assertEquals($group->name_singular, 'modified group');
    }

    /**
     * @test
     */
    public function custom_listener_works_with_invokable_class_and_can_inject_stuff()
    {
        $this->extend((new Extend\Event)->listen(Created::class, CustomListener::class));

        $group = $this->buildGroup();

        $this->assertEquals($group->name_singular, 'modified group');
    }
}

class CustomListener
{
    public function __construct()
    {
    }

    public function __invoke(Created $event)
    {
        $event->group->name_singular = 'modified group';
    }
}
