<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Nicknames\Tests\integration\security;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Security: nicknames' settings-derived rules and its describe-time default.
 */
class NicknamesDescriptorSecurityTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config('debug', false);
        $this->extension('flarum-nicknames');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'victim', 'email' => 'victim@machine.local', 'is_email_confirmed' => 1, 'nickname' => 'VictimNickSentinel'],
            ],
        ]);

        $this->setting('flarum-nicknames.regex', '^REGEX_SENTINEL[a-z]+$');
    }

    /**
     * @return array{array<string, array<string, mixed>>, string}
     */
    private function fields(int $actorId): array
    {
        $response = $this->send($this->request('GET', '/api/resource-schemas/users', ['authenticatedAs' => $actorId]));
        $body = (string) $response->getBody();

        $this->assertSame(200, $response->getStatusCode(), $body);

        return [array_column(json_decode($body, true)['data']['attributes']['fields'], null, 'name'), $body];
    }

    #[Test]
    public function a_member_who_cannot_edit_nicknames_gets_neither_the_field_nor_the_admin_regex()
    {
        [$fields, $body] = $this->fields(2);

        $this->assertArrayNotHasKey('nickname', $fields);
        $this->assertStringNotContainsString('REGEX_SENTINEL', $body);
        $this->assertStringNotContainsString('VictimNickSentinel', $body);
    }

    #[Test]
    public function an_admin_gets_the_configured_regex_as_a_pattern()
    {
        // Deliberate: the admin configured it and the server enforces it on their input.
        [$fields] = $this->fields(1);

        $this->assertStringContainsString('REGEX_SENTINEL', json_encode($fields['nickname']['constraints'] ?? []));
    }

    #[Test]
    public function the_random_username_default_is_generated_without_writes_and_never_names_a_user()
    {
        $this->setting('flarum-nicknames.random_username', true);

        $db = $this->database();
        $db->flushQueryLog();
        $db->enableQueryLog();

        [$fields, $body] = $this->fields(2);

        $writes = array_filter(
            array_column($db->getQueryLog(), 'query'),
            fn (string $sql) => preg_match('/^\s*(insert|update|delete)\b/i', $sql)
                && ! $this->isRequestBookkeeping($sql)
        );

        $this->assertSame([], array_values($writes));
        $this->assertStringNotContainsString('"victim"', $body);

        // Documents the behaviour: a freshly generated username is evaluated per describe.
        $default = $fields['username']['default'] ?? null;
        $this->assertTrue($default === null || ! User::query()->where('username', $default)->exists());
    }

    /**
     * The per-request token activity update and the actor's "last seen" bump, in any SQL dialect
     * and with any table prefix.
     */
    private function isRequestBookkeeping(string $sql): bool
    {
        $prefix = preg_quote($this->database()->getTablePrefix(), '/');

        return (bool) preg_match('/^\s*update\s+[`"]?'.$prefix.'(access_tokens[`"]?\s+set\s+[`"]?last_ip_address|users[`"]?\s+set\s+[`"]?last_seen_at)\b/i', $sql);
    }
}
