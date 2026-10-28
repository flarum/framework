<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Nicknames\Tests\integration;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * The nickname field appears in the core users descriptor.
 */
class UsersSchemaTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-nicknames');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function fields(int $actorId): array
    {
        $response = $this->send(
            $this->request('GET', '/api/resource-schemas/users', ['authenticatedAs' => $actorId])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        return array_column(json_decode($body, true)['data']['attributes']['fields'], null, 'name');
    }

    #[Test]
    public function nickname_is_described_as_write_only_for_admin()
    {
        $response = $this->send(
            $this->request('GET', '/api/resource-schemas/users', ['authenticatedAs' => 1])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $fields = array_column(json_decode($body, true)['data']['attributes']['fields'], null, 'name');

        $this->assertArrayHasKey('nickname', $fields);
        $this->assertSame('string', $fields['nickname']['kind']);
        // `->visible(false)`: the value is serialized as `displayName`, never as `nickname`.
        $this->assertFalse($fields['nickname']['visible']);
        $this->assertSame(['create' => true, 'update' => true], $fields['nickname']['writable']);
        $this->assertTrue($fields['nickname']['nullable']);
    }

    #[Test]
    public function nickname_is_absent_for_a_member_without_edit_nickname_on_others()
    {
        // `editNickname` on another (or a new) user needs `edit`; a member's
        // `user.editOwnNickname` only covers their own row, and the synthetic
        // model is never the actor.
        $fields = $this->fields(2);

        $this->assertArrayNotHasKey('nickname', $fields);
    }
}
