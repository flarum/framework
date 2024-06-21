<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class ModelPrivateTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        Discussion::unguard();
    }

    /**
     * @test
     */
    public function discussion_isnt_saved_as_private_by_default()
    {
        $this->app();

        $user = User::find(1);

        $discussion = Discussion::create([
            'title' => 'Some Discussion',
            'user_id' => $user->id,
            'created_at' => Carbon::now(),
        ]);

        $this->assertNull($discussion->is_private);
    }

    /**
     * @test
     */
    public function discussion_is_saved_as_private_if_privacy_checker_added()
    {
        $this->extend(
            (new Extend\ModelPrivate(Discussion::class))
                ->checker(function ($discussion) {
                    return $discussion->title === 'Private Discussion';
                })
        );

        $this->app();

        $user = User::find(1);

        $privateDiscussion = Discussion::create([
            'title' => 'Private Discussion',
            'user_id' => $user->id,
            'created_at' => Carbon::now(),
        ]);
        $publicDiscussion = Discussion::create([
            'title' => 'Public Discussion',
            'user_id' => $user->id,
            'created_at' => Carbon::now(),
        ]);

        $this->assertTrue($privateDiscussion->is_private);
        $this->assertFalse($publicDiscussion->is_private);
    }

    /**
     * @test
     */
    public function discussion_is_saved_as_private_if_privacy_checker_added_via_invokable_class()
    {
        $this->extend(
            (new Extend\ModelPrivate(Discussion::class))
                ->checker(CustomPrivateChecker::class)
        );

        $this->app();

        $user = User::find(1);

        $privateDiscussion = Discussion::create([
            'title' => 'Private Discussion',
            'user_id' => $user->id,
            'created_at' => Carbon::now(),
        ]);
        $publicDiscussion = Discussion::create([
            'title' => 'Public Discussion',
            'user_id' => $user->id,
            'created_at' => Carbon::now(),
        ]);

        $this->assertTrue($privateDiscussion->is_private);
        $this->assertFalse($publicDiscussion->is_private);
    }

    /**
     * @test
     */
    public function private_checkers_that_return_false_dont_matter()
    {
        $this->extend(
            (new Extend\ModelPrivate(Discussion::class))
                ->checker(function ($discussion) {
                    return false;
                })
                ->checker(CustomPrivateChecker::class)
                ->checker(function ($discussion) {
                    return false;
                })
        );

        $this->app();

        $user = User::find(1);

        $privateDiscussion = Discussion::create([
            'title' => 'Private Discussion',
            'user_id' => $user->id,
            'created_at' => Carbon::now(),
        ]);
        $publicDiscussion = Discussion::create([
            'title' => 'Public Discussion',
            'user_id' => $user->id,
            'created_at' => Carbon::now(),
        ]);

        $this->assertTrue($privateDiscussion->is_private);
        $this->assertFalse($publicDiscussion->is_private);
    }
}

class CustomPrivateChecker
{
    public function __invoke($discussion)
    {
        return $discussion->title === 'Private Discussion';
    }
}
