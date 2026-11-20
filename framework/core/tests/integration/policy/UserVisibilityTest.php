<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\policy;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Someone who may not see the forum sees no users but themselves, however the
 * query is built: including one that joins another table with an `id` column
 * of its own, as a relationship through a pivot table does.
 */
class UserVisibilityTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'alice', 'email' => 'alice@machine.local', 'is_email_confirmed' => 1],
            ],
            'group_user' => [
                ['user_id' => 2, 'group_id' => 4],
                ['user_id' => 3, 'group_id' => 4],
            ],
        ]);
    }

    private function forbidSeeingTheForum(): void
    {
        $this->database()->table('group_permission')->where('permission', 'viewForum')->delete();
    }

    #[Test]
    public function someone_who_may_not_see_the_forum_sees_only_themselves(): void
    {
        $this->forbidSeeingTheForum();

        $visible = User::query()->whereVisibleTo(User::findOrFail(2))->pluck('id')->all();

        $this->assertEquals([2], $visible);
    }

    #[Test]
    public function the_same_holds_for_a_query_joined_to_a_table_with_its_own_id(): void
    {
        $this->forbidSeeingTheForum();

        $visible = User::query()
            ->join('group_user', 'group_user.user_id', '=', 'users.id')
            ->join('groups', 'groups.id', '=', 'group_user.group_id')
            ->whereVisibleTo(User::findOrFail(2))
            ->pluck('users.id')
            ->all();

        $this->assertEquals([2], $visible);
    }
}
