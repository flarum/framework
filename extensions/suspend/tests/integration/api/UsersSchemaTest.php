<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend\Tests\integration\api;

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

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
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
            $this->assertArrayNotHasKey($name, $fields, "[$name] leaked to a member.");
        }

        // `SuspendedFilter::filter()` ignores the value unless the actor can suspend.
        $this->assertArrayNotHasKey('suspended', $filters);
    }
}
