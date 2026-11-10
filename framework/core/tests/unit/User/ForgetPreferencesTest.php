<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\User;

use Flarum\Testing\unit\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class ForgetPreferencesTest extends TestCase
{
    #[Test]
    public function only_the_given_keys_are_removed_from_the_stored_preferences()
    {
        $user = new User();
        $user->setRawAttributes(['preferences' => json_encode(['a' => false, 'b' => true, 'c' => 1])]);

        $user->forgetPreferences(['a', 'c', 'missing']);

        $this->assertSame(['b' => true], json_decode($user->getAttributes()['preferences'], true));
    }

    #[Test]
    public function nothing_stored_stays_empty()
    {
        $user = new User();

        $user->forgetPreferences(['a']);

        $this->assertSame([], json_decode($user->getAttributes()['preferences'], true));
    }

    #[Test]
    public function a_dotted_key_is_an_exact_key_not_a_path()
    {
        $user = new User();
        $user->setRawAttributes(['preferences' => json_encode([
            'acme' => ['layout' => 'grid', 'size' => 2],
            'acme.layout' => true,
        ])]);

        $user->forgetPreferences(['acme.layout']);

        $this->assertSame(['acme' => ['layout' => 'grid', 'size' => 2]], json_decode($user->getAttributes()['preferences'], true));
    }

    #[Test]
    public function a_dotted_key_that_is_not_stored_changes_nothing()
    {
        $user = new User();
        $user->setRawAttributes(['preferences' => json_encode(['acme' => ['layout' => 'grid', 'size' => 2]])]);

        $user->forgetPreferences(['acme.layout']);

        $this->assertSame(['acme' => ['layout' => 'grid', 'size' => 2]], json_decode($user->getAttributes()['preferences'], true));
    }
}
