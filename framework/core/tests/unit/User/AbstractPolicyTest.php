<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\User;

use Flarum\Testing\unit\TestCase;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;
use Illuminate\Events\Dispatcher;
use Mockery as m;

class AbstractPolicyTest extends TestCase
{
    private $policy;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->policy = m::mock(CustomUserPolicy::class)->makePartial();
        User::setEventDispatcher(new Dispatcher());
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_policy_can_be_called_with_object()
    {
        $allowed = $this->policy->checkAbility(new User(), 'create', new User());

        $this->assertEquals(AbstractPolicy::ALLOW, $allowed);
    }

    public function test_policy_can_be_called_with_class()
    {
        $allowed = $this->policy->checkAbility(new User(), 'edit', User::class);

        $this->assertEquals(AbstractPolicy::DENY, $allowed);
    }

    public function test_policy_converts_true_to_ALLOW()
    {
        $allowed = $this->policy->checkAbility(new User(), 'somethingRandom', User::class);

        $this->assertEquals(AbstractPolicy::ALLOW, $allowed);
    }

    public function test_policy_converts_false_to_DENY()
    {
        $allowed = $this->policy->checkAbility(new User(), 'somethingElseRandom', User::class);

        $this->assertEquals(AbstractPolicy::DENY, $allowed);
    }
}

class CustomUserPolicy extends AbstractPolicy
{
    protected $model = User::class;

    public function create(User $actor)
    {
        return $this->allow();
    }

    public function edit(User $actor, $target)
    {
        return $this->deny();
    }

    public function somethingRandom(User $actor, $target)
    {
        return true;
    }

    public function somethingElseRandom(User $actor, $target)
    {
        return false;
    }
}
