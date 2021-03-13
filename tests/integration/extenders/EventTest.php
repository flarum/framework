<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Foundation\Application;
use Flarum\Group\Command\CreateGroup;
use Flarum\Group\Event\Created;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Contracts\Translation\TranslatorInterface;

class EventTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function buildGroup()
    {
        $bus = $this->app()->getContainer()->make(BusDispatcher::class);

        return $bus->dispatch(
            new CreateGroup(User::find(1), ['attributes' => [
                'nameSingular' => 'test group',
                'namePlural' => 'test groups',
                'color' => '#000000',
                'icon' => 'fas fa-crown',
            ]])
        );
    }

    /**
     * @test
     */
    public function custom_listener_doesnt_work_by_default()
    {
        $group = $this->buildGroup();

        $this->assertEquals('test group', $group->name_singular);
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

        $this->assertEquals('modified group', $group->name_singular);
    }

    /**
     * @test
     */
    public function custom_listener_works_with_class_with_handle_method_and_can_inject_stuff()
    {
        // Because it injects a translator, this also tests that stuff can be injected into this callback.
        $this->extend((new Extend\Event)->listen(Created::class, CustomListener::class));

        $group = $this->buildGroup();

        $this->assertEquals('Admin', $group->name_singular);
    }

    /**
     * @test
     */
    public function custom_subscriber_works()
    {
        // Because it injects a translator, this also tests that stuff can be injected into this callback.
        $this->extend((new Extend\Event)->subscribe(CustomSubscriber::class));

        $group = $this->buildGroup();

        $this->assertEquals('Admin', $group->name_singular);
    }

    /**
     * @test
     */
    public function custom_subscriber_applied_after_app_booted()
    {
        // Because it injects a translator, this also tests that stuff can be injected into this callback.
        $this->extend((new Extend\Event)->subscribe(CustomSubscriber::class));

        $group = $this->buildGroup();

        $this->assertEquals('booted', $group->name_plural);
    }
}

class CustomListener
{
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function handle(Created $event)
    {
        $event->group->name_singular = $this->translator->trans('core.group.admin');
    }
}

class CustomSubscriber
{
    protected $bootedAtConstruct;
    protected $translator;

    public function __construct(Application $app, TranslatorInterface $translator)
    {
        $this->bootedAtConstruct = $app->isBooted();
        $this->translator = $translator;
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(Created::class, [$this, 'whenGroupCreated']);
    }

    public function whenGroupCreated(Created $event)
    {
        $event->group->name_singular = $this->translator->trans('core.group.admin');
        $event->group->name_plural = $this->bootedAtConstruct ? 'booted' : 'not booted';
    }
}
