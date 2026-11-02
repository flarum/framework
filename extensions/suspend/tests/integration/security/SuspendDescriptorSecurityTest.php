<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend\Tests\integration\security;

use Carbon\Carbon;
use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Security: suspend's fields and filter in the users descriptor, for a suspended
 * user, a suspender and a suspended suspender.
 */
class SuspendDescriptorSecurityTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private const array SUSPEND_FIELDS = ['suspendedUntil', 'suspendReason', 'suspendMessage', 'canSuspend'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->config('debug', false);
        $this->extension('flarum-suspend');

        $future = Carbon::now()->addYear()->toDateTimeString();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'suspended', 'email' => 'suspended@machine.local', 'is_email_confirmed' => 1, 'suspended_until' => $future, 'suspend_reason' => 'REASON_SENTINEL', 'suspend_message' => 'MESSAGE_SENTINEL'],
                ['id' => 4, 'username' => 'suspender', 'email' => 'suspender@machine.local', 'is_email_confirmed' => 1],
                ['id' => 5, 'username' => 'suspendedmod', 'email' => 'suspendedmod@machine.local', 'is_email_confirmed' => 1, 'suspended_until' => $future],
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
     * @return array{int, array<string, mixed>|null, string}
     */
    private function describe(int $actorId): array
    {
        $response = $this->send($this->request('GET', '/api/resource-schemas/users', ['authenticatedAs' => $actorId]));
        $body = (string) $response->getBody();

        return [$response->getStatusCode(), json_decode($body, true)['data']['attributes'] ?? null, $body];
    }

    #[Test]
    public function a_suspended_user_learns_nothing_about_suspension_fields_or_filter()
    {
        [$status, $descriptor, $body] = $this->describe(3);

        // Sign-up is open by default, so even a suspended user may describe users.
        $this->assertContains($status, [200, 403], $body);

        if ($status !== 200) {
            return;
        }

        $fields = array_column($descriptor['fields'], null, 'name');
        $filters = array_column($descriptor['filters'] ?? [], null, 'key');

        foreach (['suspendedUntil', 'suspendReason', 'suspendMessage'] as $name) {
            $this->assertArrayNotHasKey($name, $fields, "$name described to a suspended user");
        }

        $this->assertArrayNotHasKey('suspended', $filters);
        $this->assertStringNotContainsString('REASON_SENTINEL', $body);
        $this->assertStringNotContainsString('MESSAGE_SENTINEL', $body);
        $this->assertArrayNotHasKey('delete', $descriptor['capabilities']);
    }

    #[Test]
    public function a_suspended_user_cannot_describe_users_once_sign_up_is_closed()
    {
        $this->setting('allow_sign_up', false);

        [$status, , $body] = $this->describe(3);

        $this->assertSame(403, $status, $body);
    }

    #[Test]
    public function a_suspender_gets_the_suspension_fields_and_filter()
    {
        [$status, $descriptor, $body] = $this->describe(4);

        $this->assertSame(200, $status, $body);

        $fields = array_column($descriptor['fields'], null, 'name');

        $this->assertTrue($fields['suspendedUntil']['writable']['update'] ?? false);
        $this->assertArrayHasKey('suspended', array_column($descriptor['filters'], null, 'key'));
        $this->assertStringNotContainsString('REASON_SENTINEL', $body);
    }

    #[Test]
    public function a_suspended_suspender_loses_the_suspension_fields_and_filter()
    {
        [$status, $descriptor, $body] = $this->describe(5);

        if ($status === 200) {
            $fields = array_column($descriptor['fields'], null, 'name');

            foreach (['suspendedUntil', 'suspendReason', 'suspendMessage'] as $name) {
                $this->assertArrayNotHasKey($name, $fields, "$name described to a suspended suspender");
            }

            $this->assertArrayNotHasKey('suspended', array_column($descriptor['filters'] ?? [], null, 'key'));
        } else {
            $this->assertSame(403, $status, $body);
        }
    }
}
