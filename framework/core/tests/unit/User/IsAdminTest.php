<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\User;

use Flarum\Group\Group;
use Flarum\User\Guest;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Every permission check asks whether the actor is an administrator, and the
 * answer was worked out again each time: a list's serialization runs hundreds
 * of checks on the same users.
 */
class IsAdminTest extends TestCase
{
    #[Test]
    public function a_guest_is_never_an_administrator(): void
    {
        // Without looking its groups up: these unit tests have no database.
        $this->assertFalse((new Guest)->isAdmin());
    }

    #[Test]
    public function the_answer_is_worked_out_once_per_set_of_groups(): void
    {
        $user = new User;
        $user->setRelation('groups', $groups = new CountingCollection([$this->group(Group::MEMBER_ID)]));

        foreach (range(1, 5) as $check) {
            $this->assertFalse($user->isAdmin());
        }

        $this->assertSame(1, $groups->lookups, 'Each check looked through the groups again.');
    }

    #[Test]
    public function the_answer_follows_the_groups_when_they_change(): void
    {
        $user = new User;
        $user->setRelation('groups', new Collection([$this->group(Group::MEMBER_ID)]));

        $this->assertFalse($user->isAdmin());

        $user->setRelation('groups', new Collection([$this->group(Group::MEMBER_ID), $this->group(Group::ADMINISTRATOR_ID)]));

        $this->assertTrue($user->isAdmin());

        $user->setRelation('groups', new Collection());

        $this->assertFalse($user->isAdmin());
    }

    private function group(int $id): Group
    {
        return (new Group)->forceFill(['id' => $id]);
    }
}

class CountingCollection extends Collection
{
    public int $lookups = 0;

    public function contains($key, $operator = null, $value = null)
    {
        $this->lookups++;

        return parent::contains(...func_get_args());
    }
}
