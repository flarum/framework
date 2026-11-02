<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Suspend's filter and fields appear in the core users descriptor.
 */
class UsersSchemaTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-suspend');

        $future = Carbon::now()->addYear()->toDateTimeString();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'suspended', 'email' => 'suspended@machine.local', 'is_email_confirmed' => 1, 'suspended_until' => $future, 'suspend_reason' => 'STORED_REASON', 'suspend_message' => 'STORED_MESSAGE'],
                ['id' => 4, 'username' => 'suspender', 'email' => 'suspender@machine.local', 'is_email_confirmed' => 1],
                ['id' => 5, 'username' => 'suspendedsuspender', 'email' => 'suspendedsuspender@machine.local', 'is_email_confirmed' => 1, 'suspended_until' => $future],
            ],
            Group::class => [
                ['id' => 10, 'name_singular' => 'Suspender', 'name_plural' => 'Suspenders'],
            ],
            'group_user' => [
                ['user_id' => 4, 'group_id' => 10],
                ['user_id' => 5, 'group_id' => 10],
            ],
            'group_permission' => [
                ['permission' => 'user.suspend', 'group_id' => 10],
                ['permission' => 'searchUsers', 'group_id' => 10],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function describe(int $actorId): array
    {
        $response = $this->send(
            $this->request('GET', '/api/resource-schemas/users', ['authenticatedAs' => $actorId])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        return json_decode($body, true)['data']['attributes'];
    }

    #[Test]
    public function suspended_filter_is_described_as_boolean()
    {
        $filters = array_column($this->describe(1)['filters'], null, 'key');

        $this->assertArrayHasKey('suspended', $filters);
        $this->assertSame('boolean', $filters['suspended']['kind']);
    }

    #[Test]
    public function suspended_until_is_a_column_writable_on_update_for_admin()
    {
        $fields = array_column($this->describe(1)['fields'], null, 'name');

        $this->assertArrayHasKey('suspendedUntil', $fields);
        $this->assertSame(60, $fields['suspendedUntil']['ui']['column'] ?? null);
        $this->assertTrue($fields['suspendedUntil']['writable']['update'] ?? false);
    }

    #[Test]
    public function a_member_who_cannot_suspend_gets_neither_the_fields_nor_the_filter()
    {
        $descriptor = $this->describe(2);

        $fields = array_column($descriptor['fields'], null, 'name');
        $filters = array_column($descriptor['filters'] ?? [], null, 'key');

        foreach (['suspendedUntil', 'suspendReason', 'suspendMessage'] as $name) {
            $this->assertArrayNotHasKey($name, $fields, "[$name] described to a member.");
        }

        // `SuspendedFilter::filter()` ignores the value unless the actor can suspend.
        $this->assertArrayNotHasKey('suspended', $filters);
    }

    #[Test]
    public function a_suspender_gets_the_fields_and_filter_but_not_once_suspended_themselves()
    {
        $suspender = $this->describe(4);

        $this->assertTrue(array_column($suspender['fields'], null, 'name')['suspendedUntil']['writable']['update'] ?? false);
        $this->assertArrayHasKey('suspended', array_column($suspender['filters'], null, 'key'));
        $this->assertStringNotContainsString('STORED_', json_encode($suspender));

        // Sign-up is open by default, so suspended users may describe users too.
        foreach ([3, 5] as $actorId) {
            $descriptor = $this->describe($actorId);
            $fields = array_column($descriptor['fields'], null, 'name');

            foreach (['suspendedUntil', 'suspendReason', 'suspendMessage'] as $name) {
                $this->assertArrayNotHasKey($name, $fields, "[$name] described to user $actorId.");
            }

            $this->assertArrayNotHasKey('suspended', array_column($descriptor['filters'] ?? [], null, 'key'));
            $this->assertStringNotContainsString('STORED_', json_encode($descriptor));
        }
    }
}
