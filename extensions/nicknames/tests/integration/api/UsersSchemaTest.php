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

    #[Test]
    public function the_configured_regex_is_a_pattern_for_admins_only()
    {
        $this->setting('flarum-nicknames.regex', '^CONFIGURED_REGEX[a-z]+$');

        $this->assertStringContainsString('CONFIGURED_REGEX', json_encode($this->fields(1)['nickname']['constraints'] ?? []));
        $this->assertStringNotContainsString('CONFIGURED_REGEX', json_encode($this->fields(2)));
    }

    #[Test]
    public function the_random_username_default_is_generated_without_writes_and_names_no_user()
    {
        $this->setting('flarum-nicknames.random_username', true);

        $db = $this->database();
        $db->flushQueryLog();
        $db->enableQueryLog();

        $fields = $this->fields(2);

        $prefix = preg_quote($db->getTablePrefix(), '/');
        $writes = array_filter(
            array_column($db->getQueryLog(), 'query'),
            // The per-request token activity update and "last seen" bump are not the describer's.
            fn (string $sql) => preg_match('/^\s*(insert|update|delete)\b/i', $sql)
                && ! preg_match('/^\s*update\s+[`"]?'.$prefix.'(access_tokens[`"]?\s+set\s+[`"]?last_ip_address|users[`"]?\s+set\s+[`"]?last_seen_at)\b/i', $sql)
        );

        $this->assertSame([], array_values($writes));

        $default = $fields['username']['default'] ?? null;
        $this->assertTrue($default === null || ! User::query()->where('username', $default)->exists());
    }
}
