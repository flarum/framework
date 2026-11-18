<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\user;

use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class UserFactoryTest extends TestCase
{
    #[Test]
    public function factory_generated_usernames_and_emails_do_not_collide_within_a_test()
    {
        $this->prepareDatabase([
            User::class => array_map(fn (int $id) => ['id' => $id], range(100, 1099)),
        ]);

        $users = $this->database()->table('users')->where('id', '>=', 100);

        $this->assertEquals(1000, $users->count());
        $this->assertEquals(1000, $users->distinct()->count('username'));
        $this->assertEquals(1000, $users->distinct()->count('email'));
    }
}
