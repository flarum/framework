<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Group;

use Flarum\Group\PermissionCache;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PermissionCacheTest extends TestCase
{
    private function counter(int &$calls, mixed $value = true): \Closure
    {
        return function () use (&$calls, $value) {
            $calls++;

            return $value;
        };
    }

    #[Test]
    public function a_remembered_value_is_worked_out_once_per_group_set()
    {
        $cache = new PermissionCache();
        $calls = 0;

        $this->assertTrue($cache->remember([2, 3], 'verdict', $this->counter($calls)));
        $this->assertTrue($cache->remember([3, 2, 2], 'verdict', $this->counter($calls)), 'the same set, in another order');

        $this->assertSame(1, $calls);
    }

    #[Test]
    public function a_falsy_value_is_remembered_too()
    {
        $cache = new PermissionCache();
        $calls = 0;

        $this->assertNull($cache->remember([2], 'verdict', $this->counter($calls, null)));
        $this->assertNull($cache->remember([2], 'verdict', $this->counter($calls, null)));

        $this->assertSame(1, $calls);
    }

    #[Test]
    public function group_sets_and_keys_are_remembered_apart()
    {
        $cache = new PermissionCache();

        $cache->remember([2], 'verdict', fn () => 'guest');
        $cache->remember([2, 3], 'verdict', fn () => 'member');
        $cache->remember([2], 'other', fn () => 'other');

        $this->assertSame('guest', $cache->remember([2], 'verdict', fn () => 'again'));
        $this->assertSame('member', $cache->remember([2, 3], 'verdict', fn () => 'again'));
        $this->assertSame('other', $cache->remember([2], 'other', fn () => 'again'));
    }

    #[Test]
    public function flushing_forgets_remembered_values()
    {
        $cache = new PermissionCache();
        $cache->remember([2], 'verdict', fn () => 'before');

        $cache->flush();

        $this->assertSame('after', $cache->remember([2], 'verdict', fn () => 'after'));
    }

    #[Test]
    public function forgetting_a_group_forgets_the_values_of_sets_containing_it()
    {
        $cache = new PermissionCache();
        $cache->remember([2], 'verdict', fn () => 'guest before');
        $cache->remember([2, 3], 'verdict', fn () => 'member before');

        $cache->forgetGroup(3);

        $this->assertSame('guest before', $cache->remember([2], 'verdict', fn () => 'guest after'));
        $this->assertSame('member after', $cache->remember([2, 3], 'verdict', fn () => 'member after'));
    }
}
