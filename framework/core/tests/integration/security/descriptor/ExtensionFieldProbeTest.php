<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\security\descriptor;

use Flarum\Api\Context;
use Flarum\Api\Resource\UserResource;
use Flarum\Api\Schema;
use Flarum\Extend;
use Flarum\Group\Group;
use Flarum\User\User;
use Illuminate\Validation\Rule;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

// Only test files are loaded by path; the root autoloader maps Flarum\Tests elsewhere.
require_once __DIR__.'/AbstractDescriptorSecurityTestCase.php';

/**
 * Security: fields an extension adds to a describable resource. The describer
 * evaluates their closures (visibility, writability, rules, messages, default)
 * at describe time, for every actor who can describe the type.
 */
class ExtensionFieldProbeTest extends AbstractDescriptorSecurityTestCase
{
    public const string EXCEPTION_SECRET = 'EXCEPTION_SECRET /etc/shadow db-password=hunter2';

    public static int $setterCalls = 0;
    public static int $defaultCalls = 0;

    protected function setUp(): void
    {
        parent::setUp();

        self::$setterCalls = 0;
        self::$defaultCalls = 0;

        $this->extend(
            (new Extend\ApiResource(UserResource::class))->fields(fn () => [
                Schema\Str::make('probeRulesThrow')
                    ->visible(false)
                    ->writable()
                    ->rule(fn () => throw new RuntimeException(self::EXCEPTION_SECRET)),
                Schema\Str::make('probeVisibleThrows')
                    ->visible(fn () => throw new RuntimeException(self::EXCEPTION_SECRET)),
                Schema\Str::make('probeMessagesThrow')
                    ->writable()
                    ->maxLength(5)
                    ->validationMessages(['probeMessagesThrow.max' => 'core.does.not.exist'])
                    ->default(fn () => throw new RuntimeException(self::EXCEPTION_SECRET)),
                Schema\Str::make('probeUniqueExists')
                    ->visible(false)
                    ->writable()
                    ->unique('users', 'email')
                    ->rule('exists:groups,name_singular'),
                Schema\Str::make('probeSetter')
                    ->visible(false)
                    ->writable()
                    ->set(function () {
                        self::$setterCalls++;
                    })
                    ->save(function () {
                        self::$setterCalls++;
                    }),
                // An extension computing a server-side default from data.
                Schema\Str::make('probeDefaultFromData')
                    ->visible(false)
                    ->writable(fn (User $user, Context $context) => $context->creating())
                    ->default(function () {
                        self::$defaultCalls++;

                        // e.g. "invite on behalf of the newest member": data the actor never sees.
                        return User::query()->orderByDesc('id')->value('email');
                    }),
                Schema\Str::make('probeStaticDefault')
                    ->visible(false)
                    ->writable(fn (User $user, Context $context) => $context->creating())
                    ->default('static-default'),
                // An extension validating against a list read from the database.
                Schema\Str::make('probeInGroups')
                    ->visible(false)
                    ->writable()
                    ->rule(fn () => Rule::in(Group::query()->pluck('name_singular')->all())),
                Schema\Str::make('probeNotInEmails')
                    ->visible(false)
                    ->writable()
                    ->rule(fn () => Rule::notIn(User::query()->pluck('email')->all())),
            ]),
        );
    }

    #[Test]
    public function an_exception_thrown_while_describing_never_reaches_the_response()
    {
        foreach ([false, true] as $debug) {
            $this->config('debug', $debug);

            foreach (self::ACTORS as $id) {
                [$status, $descriptor, $body] = $this->describeRaw('users', $id);

                $this->assertSame(200, $status, $body);
                $this->assertStringNotContainsString('EXCEPTION_SECRET', $body, 'debug='.var_export($debug, true));
                $this->assertStringNotContainsString('hunter2', $body);
                $this->assertStringNotContainsString('RuntimeException', $body);

                $fields = $this->fieldsOf($descriptor);

                // Fails closed: the field is described, the server decides.
                $this->assertTrue($fields['probeRulesThrow']['requiredConditional'] ?? false);
                $this->assertArrayNotHasKey('constraints', $fields['probeRulesThrow']);
                // A visibility check that throws hides the field.
                $this->assertArrayNotHasKey('probeVisibleThrows', $fields);
                // A default that throws is left out.
                $this->assertArrayNotHasKey('default', $fields['probeMessagesThrow']);
            }
        }
    }

    #[Test]
    public function a_missing_translation_key_in_messages_is_not_echoed_as_a_key()
    {
        $fields = $this->fieldsOf($this->describe('users', self::ACTORS['member']));

        $this->assertSame('core.does.not.exist', $fields['probeMessagesThrow']['constraints']['messages']['maxLength'] ?? 'core.does.not.exist');
    }

    #[Test]
    public function unique_and_exists_rules_never_name_tables_or_columns()
    {
        $field = $this->fieldsOf($this->describe('users', self::ACTORS['member']))['probeUniqueExists'];
        $json = json_encode($field);

        $this->assertStringNotContainsString('name_singular', $json);
        $this->assertStringNotContainsString('groups', $json);
        $this->assertStringNotContainsString('"users"', $json);
        $this->assertTrue($field['ui']['liveValidate'] ?? false);
    }

    #[Test]
    public function describing_never_calls_setters_or_savers_or_writes()
    {
        $db = $this->database();
        $db->flushQueryLog();
        $db->enableQueryLog();

        foreach (self::ACTORS as $id) {
            foreach (['users', 'groups', 'access-tokens'] as $type) {
                $this->describeRaw($type, $id);
            }
        }

        // The auth middleware's per-request token activity update is not the describer's.
        $writes = array_filter(
            array_column($db->getQueryLog(), 'query'),
            fn (string $sql) => preg_match('/^\s*(insert|update|delete|replace|alter|create|drop|truncate)\b/i', $sql)
                && ! $this->isRequestBookkeeping($sql)
        );

        $this->assertSame(0, self::$setterCalls);
        $this->assertSame([], array_values($writes));
    }

    /**
     * `default` closures are server-side only: the describer never evaluates
     * them, so a value computed from data the actor cannot see is not published.
     */
    #[Test]
    public function a_closure_default_is_never_evaluated_or_published()
    {
        $fields = $this->fieldsOf($this->describe('users', self::ACTORS['member']));

        $this->assertSame(0, self::$defaultCalls, 'default closure evaluated at describe time');
        $this->assertArrayNotHasKey('default', $fields['probeDefaultFromData']);
    }

    #[Test]
    public function a_static_default_is_published()
    {
        $this->assertSame('static-default', $this->fieldsOf($this->describe('users', self::ACTORS['member']))['probeStaticDefault']['default'] ?? null);
    }

    /**
     * An `in:` list built by a closure (here from the database, including
     * hidden group names a member cannot list) is not published: the server
     * checks it on blur.
     */
    #[Test]
    public function an_in_rule_built_by_a_closure_is_left_to_the_server()
    {
        $field = $this->fieldsOf($this->describe('users', self::ACTORS['member']))['probeInGroups'];

        $this->assertNotContains('SecretCabalSingular', $field['constraints']['enum'] ?? [], 'hidden group name published via constraints.enum');
        $this->assertArrayNotHasKey('constraints', $field);
        $this->assertTrue($field['ui']['liveValidate'] ?? false);
    }

    /**
     * A `not_in:` deny-list (here every user's email address) is never published.
     */
    #[Test]
    public function a_not_in_rule_is_never_published()
    {
        $field = $this->fieldsOf($this->describe('users', self::ACTORS['member']))['probeNotInEmails'];

        $this->assertStringNotContainsString('victim-secret@leak.test', json_encode($field));
        $this->assertArrayNotHasKey('constraints', $field);
        $this->assertTrue($field['ui']['liveValidate'] ?? false);
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
