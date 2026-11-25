<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\user;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Flarum\User\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;

class UserRepositoryTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    private function users(): UserRepository
    {
        return $this->app()->getContainer()->make(UserRepository::class);
    }

    #[Test]
    public function find_all_by_email_returns_the_accounts_with_that_address(): void
    {
        $users = $this->users()->findAllByEmail('normal@machine.local');

        $this->assertInstanceOf(Collection::class, $users);
        $this->assertSame([2], $users->pluck('id')->all());
    }

    #[Test]
    public function find_all_by_email_returns_an_empty_collection_for_an_unknown_address(): void
    {
        $users = $this->users()->findAllByEmail('nobody@machine.local');

        $this->assertInstanceOf(Collection::class, $users);
        $this->assertTrue($users->isEmpty());
    }
}
