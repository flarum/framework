<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\resource_schemas;

use Flarum\Api\Context;
use Flarum\Api\Resource\UserResource;
use Flarum\Api\Schema;
use Flarum\Extend;
use Flarum\Group\Group;
use Flarum\Http\AccessToken;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Validation\Rule;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

/**
 * What a descriptor tells each actor: only the type-level shape their permissions allow,
 * never data, settings or server internals, whatever the request or the fields carry.
 */
class DescriptorPrivacyTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private const array ACTORS = ['admin' => 1, 'member' => 2, 'moderator' => 3, 'unconfirmed' => 4];

    private const array TYPES = ['users', 'groups', 'access-tokens'];

    /** Values stored in the database or settings; no descriptor carries them. */
    private const array STORED_VALUES = [
        'stored-email@machine.local',
        'storedusername',
        'HiddenGroupSingular',
        'HiddenGroupPlural',
        'SETTING_VALUE_9f3a',
        'storedtoken0123456789abcdef0123456789ab',
        'StoredTokenTitle',
        '10.66.66.66',
    ];

    private const string EXCEPTION_MESSAGE = 'EXCEPTION_MESSAGE db-password=hunter2';

    public static int $sideEffects = 0;

    protected function setUp(): void
    {
        parent::setUp();

        // The test install runs in debug mode; production does not.
        $this->config('debug', false);

        self::$sideEffects = 0;

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'moderator', 'email' => 'moderator@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'unconfirmed', 'email' => 'unconfirmed@machine.local', 'is_email_confirmed' => 0],
                ['id' => 5, 'username' => 'storedusername', 'email' => 'stored-email@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => 10, 'name_singular' => 'Mod', 'name_plural' => 'Mods', 'is_hidden' => 0],
                ['id' => 11, 'name_singular' => 'HiddenGroupSingular', 'name_plural' => 'HiddenGroupPlural', 'is_hidden' => 1],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 10],
                ['user_id' => 5, 'group_id' => 11],
            ],
            'group_permission' => [
                // Neither editCredentials nor viewLastSeenAt nor moderateAccessTokens.
                ['permission' => 'user.edit', 'group_id' => 10],
                ['permission' => 'user.editGroups', 'group_id' => 10],
                ['permission' => 'searchUsers', 'group_id' => 10],
            ],
            AccessToken::class => [
                ['id' => 50, 'token' => 'storedtoken0123456789abcdef0123456789ab', 'user_id' => 5, 'type' => 'developer', 'title' => 'StoredTokenTitle', 'last_ip_address' => '10.66.66.66', 'created_at' => '2024-01-01 00:00:00', 'last_activity_at' => '2024-01-01 00:00:00'],
            ],
        ]);

        $this->setting('mail_password', 'SETTING_VALUE_9f3a');

        // Fields an extension adds: the describer evaluates their closures for every actor.
        $this->extend(
            (new Extend\ApiResource(UserResource::class))->fields(fn () => [
                Schema\Str::make('rulesThrow')
                    ->visible(false)
                    ->writable()
                    ->rule(fn () => throw new RuntimeException(self::EXCEPTION_MESSAGE)),
                Schema\Str::make('visibilityThrows')
                    ->visible(fn () => throw new RuntimeException(self::EXCEPTION_MESSAGE)),
                Schema\Str::make('defaultThrows')
                    ->writable()
                    ->default(fn () => throw new RuntimeException(self::EXCEPTION_MESSAGE)),
                Schema\Str::make('uniqueAndExists')
                    ->visible(false)
                    ->writable()
                    ->unique('users', 'email')
                    ->rule('exists:groups,name_singular'),
                Schema\Str::make('withSetter')
                    ->visible(false)
                    ->writable()
                    ->set(fn () => self::$sideEffects++)
                    ->save(fn () => self::$sideEffects++),
                Schema\Str::make('defaultFromData')
                    ->visible(false)
                    ->writable(fn (User $user, Context $context) => $context->creating())
                    ->default(function () {
                        self::$sideEffects++;

                        return User::query()->orderByDesc('id')->value('email');
                    }),
                Schema\Str::make('staticDefault')
                    ->visible(false)
                    ->writable(fn (User $user, Context $context) => $context->creating())
                    ->default('static-default'),
                Schema\Str::make('inGroupNames')
                    ->visible(false)
                    ->writable()
                    ->rule(fn () => Rule::in(Group::query()->pluck('name_singular')->all())),
                Schema\Str::make('notInEmails')
                    ->visible(false)
                    ->writable()
                    ->rule(fn () => Rule::notIn(User::query()->pluck('email')->all())),
            ]),
        );
    }

    /**
     * @return array{0: int, 1: array<string, mixed>|null, 2: string}
     */
    private function describeRaw(string $type, ?int $actorId): array
    {
        $response = $this->send(
            $this->request('GET', '/api/resource-schemas/'.$type, $actorId === null ? [] : ['authenticatedAs' => $actorId])
        );
        $body = (string) $response->getBody();

        return [$response->getStatusCode(), json_decode($body, true)['data']['attributes'] ?? null, $body];
    }

    /**
     * @return array<string, mixed>
     */
    private function describe(string $type, int $actorId): array
    {
        [$status, $descriptor, $body] = $this->describeRaw($type, $actorId);

        $this->assertSame(200, $status, $body);

        return $descriptor;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function fields(array $descriptor): array
    {
        return array_column($descriptor['fields'] ?? [], null, 'name');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function filters(array $descriptor): array
    {
        return array_column($descriptor['filters'] ?? [], null, 'key');
    }

    private function assertCarriesNoStoredValuesOrInternals(string $body, string $context): void
    {
        foreach ([...self::STORED_VALUES, 'EXCEPTION_MESSAGE', 'hunter2', 'RuntimeException'] as $value) {
            $this->assertStringNotContainsString($value, $body, "$context: [$value]");
        }

        $this->assertCarriesNoInternals($body, $context);
    }

    private function assertCarriesNoInternals(string $body, string $context): void
    {
        foreach (['Flarum\\\\', 'Illuminate\\\\', 'Tobyz\\\\', '.php', '/Users/', '/var/', 'SQLSTATE', 'Stack trace'] as $internal) {
            $this->assertStringNotContainsString($internal, $body, "$context: [$internal]");
        }
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

    #[Test]
    public function no_descriptor_carries_stored_values_or_internals_in_or_out_of_debug_mode()
    {
        foreach (self::ACTORS as $actor => $id) {
            foreach (self::TYPES as $type) {
                [$status, $production, $body] = $this->describeRaw($type, $id);

                $this->assertContains($status, [200, 403], "$actor $type: $body");
                $this->assertCarriesNoStoredValuesOrInternals($body, "$actor $type");

                $this->config('debug', true);
                [, $debug, $debugBody] = $this->describeRaw($type, $id);
                $this->config('debug', false);

                $this->assertSame($production, $debug, "$actor $type differs in debug mode");
                $this->assertCarriesNoStoredValuesOrInternals($debugBody, "debug $actor $type");
            }
        }
    }

    #[Test]
    public function guests_get_the_same_401_for_every_type()
    {
        [$status, , $reference] = $this->describeRaw('does-not-exist', null);

        $this->assertSame(401, $status, $reference);

        foreach ([...self::TYPES, 'posts', 'resource-schemas', UserResource::class, '..', 'users%00'] as $type) {
            [$status, , $body] = $this->describeRaw($type, null);

            $this->assertSame(401, $status, "$type: $body");
            $this->assertSame($reference, $body, $type);
        }
    }

    #[Test]
    public function an_invalid_type_is_not_found_without_internals()
    {
        $types = ['posts', 'resource-schemas', 'settings', 'does-not-exist', UserResource::class, '..%2F..%2Fetc%2Fpasswd', '%E2%80%AEusers', 'users%00', str_repeat('a', 8000), "users'%20OR%201=1--"];

        foreach ([false, true] as $debug) {
            $this->config('debug', $debug);

            foreach ($types as $type) {
                [$status, , $body] = $this->describeRaw($type, self::ACTORS['member']);

                $this->assertSame(404, $status, "$type: $body");

                // The 404 echoes the requested id back; only what the server adds counts.
                if (! $debug) {
                    $this->assertCarriesNoInternals(str_replace(trim(json_encode(urldecode($type)), '"'), '', $body), $type);
                }
            }
        }

        foreach ([['GET', '/api/resource-schemas'], ['POST', '/api/resource-schemas'], ['PATCH', '/api/resource-schemas/users'], ['DELETE', '/api/resource-schemas/users']] as [$method, $path]) {
            $response = $this->send($this->request($method, $path, ['authenticatedAs' => 1, 'json' => []]));

            $this->assertContains($response->getStatusCode(), [404, 405], "$method $path");
        }
    }

    #[Test]
    public function a_moderator_and_an_admin_get_exactly_their_permissions()
    {
        $moderator = $this->describe('users', self::ACTORS['moderator']);
        $fields = $this->fields($moderator);
        $filters = $this->filters($moderator);

        // user.editGroups
        $this->assertSame(['update' => true], $fields['groups']['writable'] ?? null);
        // no user.editCredentials
        $this->assertFalse($fields['email']['visible'] ?? true);
        $this->assertArrayNotHasKey('update', $fields['email']['writable'] ?? []);
        $this->assertArrayNotHasKey('update', $fields['password']['writable'] ?? []);
        $this->assertArrayNotHasKey('isEmailConfirmed', $fields);
        $this->assertArrayNotHasKey('email', $filters);
        // no user.viewLastSeenAt
        $this->assertArrayNotHasKey('lastSeenAt', $filters);
        // no user.delete
        $this->assertArrayNotHasKey('delete', $moderator['capabilities']);
        // no moderateAccessTokens
        $this->assertArrayNotHasKey('user', $this->filters($this->describe('access-tokens', self::ACTORS['moderator'])));

        $admin = $this->describe('users', self::ACTORS['admin']);

        $this->assertNotFalse($this->fields($admin)['email']['visible'] ?? true);
        $this->assertArrayHasKey('isEmailConfirmed', $this->fields($admin));
        $this->assertArrayHasKey('email', $this->filters($admin));
        $this->assertArrayHasKey('lastSeenAt', $this->filters($admin));
        $this->assertTrue($admin['capabilities']['delete'] ?? false);
        $this->assertArrayHasKey('user', $this->filters($this->describe('access-tokens', self::ACTORS['admin'])));
    }

    #[Test]
    public function with_sign_up_closed_the_sign_up_fields_go_and_an_actor_without_list_access_is_refused()
    {
        $this->setting('allow_sign_up', false);

        $descriptor = $this->describe('users', self::ACTORS['member']);

        $this->assertFalse($descriptor['capabilities']['create']);

        foreach (['email', 'password', 'token', 'colorScheme'] as $name) {
            $this->assertArrayNotHasKey($name, $this->fields($descriptor), $name);
        }

        $this->assertSame(403, $this->describeRaw('users', self::ACTORS['unconfirmed'])[0]);
    }

    #[Test]
    public function describing_runs_no_setters_or_server_side_defaults_writes_nothing_and_reads_no_rows()
    {
        $db = $this->database();
        $counts = [];

        $queries = function (string $type, int $id) use ($db): array {
            $db->flushQueryLog();
            $db->enableQueryLog();
            $this->describe($type, $id);
            $log = array_column($db->getQueryLog(), 'query');
            $db->disableQueryLog();

            return $log;
        };

        // Warm up: the first request per actor loads settings, extensions and the actor's groups.
        foreach (self::ACTORS as $id) {
            foreach (self::TYPES as $type) {
                $this->describeRaw($type, $id);
            }
        }

        foreach (self::ACTORS as $actor => $id) {
            foreach (self::TYPES as $type) {
                if ($actor === 'unconfirmed') {
                    continue;
                }

                $log = $queries($type, $id);
                $counts["$actor $type"] = count($log);

                $writes = array_filter(
                    $log,
                    fn (string $sql) => preg_match('/^\s*(insert|update|delete|replace|alter|create|drop|truncate)\b/i', $sql) && ! $this->isRequestBookkeeping($sql)
                );

                $this->assertSame([], array_values($writes), "$actor $type");
                $this->assertLessThanOrEqual(40, $counts["$actor $type"], "$actor $type");
            }
        }

        $this->assertSame(0, self::$sideEffects);

        // More rows in every described table change nothing: no rows are read.
        $users = $groups = [];

        for ($i = 100; $i < 150; $i++) {
            $users[] = ['id' => $i, 'username' => "bulk$i", 'email' => "bulk$i@machine.local", 'is_email_confirmed' => 1, 'password' => 'x', 'joined_at' => '2024-01-01 00:00:00'];
            $groups[] = ['id' => $i, 'name_singular' => "G$i", 'name_plural' => "G$i", 'is_hidden' => $i % 2];
        }

        $db->table('users')->insert($users);
        $db->table('groups')->insert($groups);

        foreach ($counts as $key => $count) {
            [$actor, $type] = explode(' ', $key);
            $this->assertSame($count, count($queries($type, self::ACTORS[$actor])), "$key grows with rows");
        }
    }

    #[Test]
    public function query_parameters_headers_and_cookies_do_not_change_the_descriptor()
    {
        foreach ([self::ACTORS['admin'], self::ACTORS['member']] as $id) {
            foreach (self::TYPES as $type) {
                [, $baseline] = $this->describeRaw($type, $id);

                $response = $this->send(
                    $this->request('GET', '/api/resource-schemas/'.$type, ['authenticatedAs' => $id, 'cookies' => ['locale' => 'de']])
                        ->withQueryParams(['filter' => ['email' => 'x', 'q' => 'y'], 'include' => 'groups', 'sort' => '-email', 'page' => ['limit' => 1000], 'authenticatedAs' => 1])
                        ->withHeader('X-HTTP-Method-Override', 'PATCH')
                        ->withHeader('Accept-Language', 'de')
                        ->withHeader('X-Forwarded-For', '127.0.0.1')
                );
                $body = (string) $response->getBody();

                // Unsupported query parameters may be rejected; they never alter a 200.
                $this->assertLessThan(500, $response->getStatusCode(), $body);

                if ($response->getStatusCode() === 200) {
                    $this->assertSame($baseline, json_decode($body, true)['data']['attributes'], "actor $id $type");
                }
            }
        }
    }

    #[Test]
    public function each_actor_gets_their_own_uncached_descriptor()
    {
        $reference = [];

        foreach ([1, 2, 3] as $id) {
            $reference[$id] = $this->describe('users', $id);
        }

        // Interleaved on the same booted app.
        foreach ([1, 2, 1, 3, 2, 1] as $id) {
            $this->assertSame($reference[$id], $this->describe('users', $id), "actor $id got another actor's descriptor");
        }

        $this->assertNotSame($reference[1], $reference[2]);

        $response = $this->send($this->request('GET', '/api/resource-schemas/users', ['authenticatedAs' => 1]));

        $this->assertFalse($response->hasHeader('ETag'));
        $this->assertStringNotContainsString('public', strtolower($response->getHeaderLine('Cache-Control')));

        // A permission change applies to the next request.
        $this->database()->table('group_user')->insert(['user_id' => 2, 'group_id' => Group::ADMINISTRATOR_ID]);
        User::find(2)->unsetRelation('groups');

        $this->assertArrayHasKey('email', $this->filters($this->describe('users', 2)));
    }

    #[Test]
    public function extension_fields_whose_closures_throw_fail_closed()
    {
        foreach ([false, true] as $debug) {
            $this->config('debug', $debug);

            $fields = $this->fields($this->describe('users', self::ACTORS['member']));

            // Rules that throw: described, required and constraints left to the server.
            $this->assertTrue($fields['rulesThrow']['requiredConditional'] ?? false);
            $this->assertArrayNotHasKey('constraints', $fields['rulesThrow']);
            // A visibility check that throws hides the field.
            $this->assertArrayNotHasKey('visibilityThrows', $fields);
            // A default that throws is left out.
            $this->assertArrayNotHasKey('default', $fields['defaultThrows']);
        }
    }

    #[Test]
    public function closure_defaults_and_closure_built_value_lists_are_not_published_but_static_defaults_are()
    {
        $descriptor = $this->describe('users', self::ACTORS['member']);
        $fields = $this->fields($descriptor);

        $this->assertArrayNotHasKey('default', $fields['defaultFromData']);
        $this->assertSame('static-default', $fields['staticDefault']['default'] ?? null);

        // An `in:` list built from rows the actor cannot list, and a `not_in:` deny-list.
        $this->assertArrayNotHasKey('constraints', $fields['inGroupNames']);
        $this->assertArrayNotHasKey('constraints', $fields['notInEmails']);

        // `unique` and `exists` name no table or column.
        $json = json_encode($fields['uniqueAndExists']);
        $this->assertStringNotContainsString('name_singular', $json);
        $this->assertStringNotContainsString('groups', $json);
        $this->assertStringNotContainsString('"users"', $json);

        // No field or filter enumerates groups, so hidden group names never surface.
        foreach (self::ACTORS as $id) {
            foreach (['users', 'groups'] as $type) {
                $this->assertStringNotContainsString('HiddenGroup', json_encode($this->describe($type, $id)), "actor $id $type");
            }
        }
    }
}
