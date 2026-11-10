<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\tools;

use Carbon\Carbon;
use Flarum\Mcp\Tests\integration\McpTestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Credentials and privileges are out of reach of the generic write tools,
 * whatever the actor may do over HTTP: a prompt injection in forum content
 * must not be able to take over an account or grant a group.
 */
class WriteRulesTest extends McpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-suspend');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'unconfirmed', 'email' => 'unconfirmed@machine.local', 'is_email_confirmed' => 0],
            ],
        ]);
    }

    /**
     * @return array<string, array{string, array<string, mixed>, array<string, mixed>}>
     */
    public static function refusedWrites(): array
    {
        return [
            'password' => ['password', ['password' => 'a-brand-new-password'], []],
            'email' => ['email', ['email' => 'attacker@example.com'], []],
            'isEmailConfirmed' => ['isEmailConfirmed', ['isEmailConfirmed' => true], []],
            'groups' => ['groups', [], ['groups' => ['1']]],
            'password with an allowed field' => ['password', ['username' => 'renamed', 'password' => 'a-brand-new-password'], []],
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $relationships
     */
    #[Test]
    #[DataProvider('refusedWrites')]
    public function a_credential_or_privilege_field_is_refused(string $field, array $attributes, array $relationships): void
    {
        $before = $this->snapshot();

        foreach (['2', '3'] as $id) {
            $error = $this->toolOutput($this->callTool('update_resource', array_filter([
                'type' => 'users',
                'id' => $id,
                'attributes' => $attributes,
                'relationships' => $relationships,
            ]), $this->bearer($this->developerToken(1))), expectError: true);

            $this->assertStringContainsString("`$field`", $error['error']);
        }

        $this->assertSame($before, $this->snapshot());
    }

    #[Test]
    public function the_refused_fields_are_described_as_not_writable(): void
    {
        $users = $this->toolOutput($this->callTool('describe_api', ['type' => 'users'], $this->bearer($this->developerToken(1))));

        $attributes = array_column($users['attributes'], 'writable', 'name');
        $relationships = array_column($users['relationships'], 'writable', 'name');

        foreach (['email', 'isEmailConfirmed'] as $name) {
            $this->assertSame(['create' => false, 'update' => false], $attributes[$name], $name);
        }

        // Write-only, so never described; nor offered as writable.
        $this->assertFalse($attributes['password']['update'] ?? false);
        $this->assertSame(['create' => false, 'update' => false], $relationships['groups']);
        $this->assertTrue($attributes['username']['update']);
        $this->assertTrue($attributes['suspendedUntil']['update']);
    }

    #[Test]
    public function other_user_fields_stay_writable(): void
    {
        $until = Carbon::now()->addWeek()->startOfSecond();

        $this->toolOutput($this->callTool('update_resource', [
            'type' => 'users',
            'id' => '2',
            'attributes' => ['username' => 'renamed', 'suspendedUntil' => $until->toIso8601String()],
        ], $this->bearer($this->developerToken(1))));

        $user = User::query()->findOrFail(2);
        $this->assertSame('renamed', $user->username);
        $this->assertNotNull($user->suspended_until);
    }

    #[Test]
    public function groups_cannot_be_written(): void
    {
        $auth = $this->bearer($this->developerToken(1));
        $groups = $this->database()->table('groups')->orderBy('id')->get()->toJson();

        $tools = array_column($this->rpcResult($this->modern('tools/list', [], $auth))['tools'], null, 'name');

        foreach (['create_resource', 'update_resource', 'delete_resource'] as $tool) {
            $this->assertNotContains('groups', $tools[$tool]['inputSchema']['properties']['type']['enum'], $tool);
        }

        $calls = [
            'create_resource' => ['type' => 'groups', 'attributes' => ['nameSingular' => 'Root', 'namePlural' => 'Roots']],
            'update_resource' => ['type' => 'groups', 'id' => '4', 'attributes' => ['nameSingular' => 'Owned']],
            'delete_resource' => ['type' => 'groups', 'id' => '4'],
        ];

        foreach ($calls as $tool => $arguments) {
            $response = $this->json($this->callTool($tool, $arguments, $auth));
            $this->assertArrayNotHasKey('result', $response, $tool.': '.json_encode($response));
        }

        $this->assertSame($groups, $this->database()->table('groups')->orderBy('id')->get()->toJson());
    }

    protected function snapshot(): string
    {
        return json_encode([
            $this->database()->table('users')->whereIn('id', [2, 3])->orderBy('id')->get(['id', 'username', 'email', 'password', 'is_email_confirmed']),
            $this->database()->table('group_user')->orderBy('user_id')->orderBy('group_id')->get(),
        ], JSON_THROW_ON_ERROR);
    }
}
