<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend\Tests\integration\api\users;

use Carbon\Carbon;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

class ListUsersTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-suspend');

        $this->prepareDatabase([
            User::class => [
                ['id' => 1, 'username' => 'Muralf', 'email' => 'muralf@machine.local', 'is_email_confirmed' => 1],
                ['id' => 2, 'username' => 'SuspendedDonny1', 'email' => 'acme1@machine.local', 'is_email_confirmed' => 1, 'suspended_until' => Carbon::now()->addDay(), 'suspend_reason' => 'acme', 'suspend_message' => 'acme'],
                ['id' => 3, 'username' => 'SuspendedDonny2', 'email' => 'acme2@machine.local', 'is_email_confirmed' => 1, 'suspended_until' => Carbon::now()->addDay(), 'suspend_reason' => 'acme', 'suspend_message' => 'acme'],
                ['id' => 4, 'username' => 'SuspendedDonny3', 'email' => 'acme3@machine.local', 'is_email_confirmed' => 1, 'suspended_until' => Carbon::now()->subDay(), 'suspend_reason' => 'acme', 'suspend_message' => 'acme'],
                ['id' => 5, 'username' => 'SuspendedDonny4', 'email' => 'acme4@machine.local', 'is_email_confirmed' => 1, 'suspended_until' => Carbon::now()->addDay(), 'suspend_reason' => 'acme', 'suspend_message' => 'acme'],
                ['id' => 6, 'username' => 'Acme', 'email' => 'acme5@machine.local', 'is_email_confirmed' => 1],
                ['id' => 7, 'username' => 'normal', 'email' => 'normal@machine.local', 'is_email_confirmed' => 1],
            ]
        ]);
    }

    public function can_view_default_users_list()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'filter' => [
                    'suspended' => true,
                ],
            ])
        );

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEqualsCanonicalizing([1, 2, 3, 4, 5, 6], Arr::pluck($body['data'], 'id'));
    }

    #[Test]
    public function can_filter_users_by_suspension()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'filter' => [
                    'suspended' => true,
                ],
            ])
        );

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEqualsCanonicalizing([2, 3, 5], Arr::pluck($body['data'], 'id'));
    }

    #[Test]
    public function suspended_filter_returns_only_currently_suspended_users()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'filter' => [
                    'suspended' => '1',
                ],
            ])
        );

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(200, $response->getStatusCode());
        // User 4's suspension has expired, so they are not currently suspended.
        $this->assertEqualsCanonicalizing([2, 3, 5], Arr::pluck($body['data'], 'id'));
    }

    #[Test]
    public function negated_suspended_filter_returns_only_users_who_are_not_suspended()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'filter' => [
                    '-suspended' => '1',
                ],
            ])
        );

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(200, $response->getStatusCode());
        // Users 1, 6 and 7 were never suspended; user 4's suspension has expired.
        $this->assertEqualsCanonicalizing([1, 4, 6, 7], Arr::pluck($body['data'], 'id'));
    }

    #[Test]
    public function suspended_filter_is_ignored_for_actor_without_suspend_permission()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 7,
            ])->withQueryParams([
                'filter' => [
                    'suspended' => '1',
                ],
            ])
        );

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEqualsCanonicalizing([1, 2, 3, 4, 5, 6, 7], Arr::pluck($body['data'], 'id'));
    }

    #[Test]
    public function negated_suspended_filter_is_ignored_for_actor_without_suspend_permission()
    {
        $response = $this->send(
            $this->request('GET', '/api/users', [
                'authenticatedAs' => 7,
            ])->withQueryParams([
                'filter' => [
                    '-suspended' => '1',
                ],
            ])
        );

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEqualsCanonicalizing([1, 2, 3, 4, 5, 6, 7], Arr::pluck($body['data'], 'id'));
    }
}
