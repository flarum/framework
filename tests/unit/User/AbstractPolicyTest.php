<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\User;

use Flarum\Event\GetPermission;
use Flarum\Tests\unit\TestCase;
use Flarum\User\User;
use Illuminate\Events\Dispatcher;
use Mockery as m;

class AbstractPolicyTest extends TestCase
{
    private $policy;
    private $dispatcher;

    public function setUp()
    {
        $this->policy = m::mock(UserPolicy::class)->makePartial();
        $this->dispatcher = new Dispatcher();
        $this->dispatcher->subscribe($this->policy);
        User::setEventDispatcher($this->dispatcher);
    }

    public function test_policy_can_be_called_with_object()
    {
        $this->policy->shouldReceive('edit')->andReturn(true);

        $allowed = $this->dispatcher->until(new GetPermission(new User(), 'edit', new User()));

        $this->assertTrue($allowed);
    }

    public function test_policy_can_be_called_with_class()
    {
        $this->policy->shouldReceive('create')->andReturn(true);

        $allowed = $this->dispatcher->until(new GetPermission(new User(), 'create', User::class));

        $this->assertTrue($allowed);
    }
}
