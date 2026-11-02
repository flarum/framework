<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\security\descriptor;

use Flarum\Group\Group;
use Flarum\Http\AccessToken;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

/**
 * Fixtures shared by the descriptor security tests: one actor per privilege
 * level, plus data that must never surface in any descriptor.
 */
abstract class AbstractDescriptorSecurityTestCase extends TestCase
{
    use RetrievesAuthorizedUsers;

    public const array ACTORS = [
        'admin' => 1,
        'member' => 2,
        'moderator' => 3,
        'unconfirmed' => 4,
    ];

    /** Values that live in the database and must never appear in a descriptor. */
    public const array SECRETS = [
        'victim-secret@leak.test',
        'victimsecretname',
        'SecretCabalSingular',
        'SecretCabalPlural',
        'SETTING_SENTINEL_9f3a',
        'tokensentinel0123456789abcdef0123456789',
        'TokenTitleSentinel',
        '10.66.66.66',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // The test install runs in debug mode; production does not.
        $this->config('debug', false);

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'moderator', 'email' => 'moderator@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'unconfirmed', 'email' => 'unconfirmed@machine.local', 'is_email_confirmed' => 0],
                ['id' => 5, 'username' => 'victimsecretname', 'email' => 'victim-secret@leak.test', 'is_email_confirmed' => 1, 'preferences' => json_encode(['discloseOnline' => false])],
            ],
            Group::class => [
                ['id' => 10, 'name_singular' => 'Mod', 'name_plural' => 'Mods', 'is_hidden' => 0],
                ['id' => 11, 'name_singular' => 'SecretCabalSingular', 'name_plural' => 'SecretCabalPlural', 'is_hidden' => 1],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 10],
                ['user_id' => 5, 'group_id' => 11],
            ],
            'group_permission' => [
                // Admin-ish, but neither editCredentials nor viewLastSeenAt nor moderateAccessTokens.
                ['permission' => 'user.edit', 'group_id' => 10],
                ['permission' => 'user.editGroups', 'group_id' => 10],
                ['permission' => 'searchUsers', 'group_id' => 10],
            ],
            AccessToken::class => [
                ['id' => 50, 'token' => 'tokensentinel0123456789abcdef0123456789', 'user_id' => 5, 'type' => 'developer', 'title' => 'TokenTitleSentinel', 'last_ip_address' => '10.66.66.66', 'created_at' => '2024-01-01 00:00:00', 'last_activity_at' => '2024-01-01 00:00:00'],
            ],
        ]);

        // A secret setting: no descriptor may carry a setting value.
        $this->setting('mail_password', 'SETTING_SENTINEL_9f3a');
    }

    /**
     * @return array{0: int, 1: array<string, mixed>|null, 2: string}
     */
    protected function describeRaw(string $type, ?int $actorId, array $options = []): array
    {
        if ($actorId !== null) {
            $options['authenticatedAs'] = $actorId;
        }

        $response = $this->send($this->request('GET', '/api/resource-schemas/'.$type, $options));
        $body = (string) $response->getBody();

        return [$response->getStatusCode(), json_decode($body, true)['data']['attributes'] ?? null, $body];
    }

    /**
     * @return array<string, mixed>
     */
    protected function describe(string $type, int $actorId): array
    {
        [$status, $descriptor, $body] = $this->describeRaw($type, $actorId);

        $this->assertSame(200, $status, $body);

        return $descriptor;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function fieldsOf(array $descriptor): array
    {
        return array_column($descriptor['fields'] ?? [], null, 'name');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function filtersOf(array $descriptor): array
    {
        return array_column($descriptor['filters'] ?? [], null, 'key');
    }

    protected function assertNoSecrets(string $body, string $context = ''): void
    {
        foreach (self::SECRETS as $secret) {
            $this->assertStringNotContainsString($secret, $body, "$context: descriptor leaks [$secret]");
        }

        foreach (['Flarum\\\\', 'Illuminate\\\\', 'Tobyz\\\\', '.php', '/Users/', '/var/', 'SQLSTATE', 'Stack trace'] as $internal) {
            $this->assertStringNotContainsString($internal, $body, "$context: descriptor leaks internals [$internal]");
        }
    }
}
