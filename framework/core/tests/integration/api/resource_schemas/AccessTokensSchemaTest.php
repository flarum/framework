<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\resource_schemas;

use Carbon\Carbon;
use Flarum\Group\Group;
use Flarum\Http\AccessToken;
use Flarum\Http\Filter\AccessTokenTypeFilter;
use Flarum\Http\Filter\UserFilter;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

/**
 * The access tokens descriptor, which core describes by default, and the admin list it drives.
 */
class AccessTokensSchemaTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'moderator', 'email' => 'moderator@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => 100, 'name_singular' => 'Token mod', 'name_plural' => 'Token mods'],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 100],
            ],
            'group_permission' => [
                ['group_id' => 100, 'permission' => 'moderateAccessTokens'],
            ],
            AccessToken::class => [
                ['id' => 1, 'token' => 'admin-dev', 'user_id' => 1, 'type' => 'developer', 'title' => 'Admin script', 'created_at' => Carbon::parse('2021-01-01 01:00:00'), 'last_activity_at' => Carbon::parse('2021-01-03 00:00:00')],
                ['id' => 2, 'token' => 'normal-session', 'user_id' => 2, 'type' => 'session', 'created_at' => Carbon::parse('2021-01-02 01:00:00'), 'last_activity_at' => Carbon::parse('2021-01-05 00:00:00')],
                ['id' => 3, 'token' => 'normal-dev', 'user_id' => 2, 'type' => 'developer', 'title' => 'Normal script', 'created_at' => Carbon::parse('2021-01-03 01:00:00'), 'last_activity_at' => Carbon::parse('2021-01-04 00:00:00')],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function describe(int $actorId): array
    {
        $response = $this->send(
            $this->request('GET', '/api/resource-schemas/access-tokens', ['authenticatedAs' => $actorId])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        return json_decode($body, true)['data']['attributes'];
    }

    /**
     * @param array<string, mixed> $descriptor
     * @return array<string, array<string, mixed>>
     */
    protected function fieldsByName(array $descriptor): array
    {
        return array_column($descriptor['fields'], null, 'name');
    }

    /**
     * @param array<string, mixed> $descriptor
     * @return array<string, array<string, mixed>>
     */
    protected function filtersByKey(array $descriptor): array
    {
        return array_column($descriptor['filters'] ?? [], null, 'key');
    }

    #[Test]
    public function access_tokens_are_describable_by_default()
    {
        $descriptor = $this->describe(1);

        $this->assertSame(1, $descriptor['schemaVersion']);
        $this->assertSame('/access-tokens', $descriptor['endpoint']);
    }

    #[Test]
    public function admin_gets_sorts_pagination_and_capabilities()
    {
        $descriptor = $this->describe(1);

        $this->assertSame(['createdAt', 'lastActivityAt'], $descriptor['sorts']);
        $this->assertSame(['defaultLimit' => 20, 'maxLimit' => 50], $descriptor['pagination']);
        $this->assertSame(['list' => true, 'create' => true, 'delete' => true], $descriptor['capabilities']);
        $this->assertArrayNotHasKey('search', $descriptor);
        $this->assertArrayNotHasKey('defaultSort', $descriptor);
    }

    #[Test]
    public function admin_gets_the_column_fields_with_labels()
    {
        $fields = $this->fieldsByName($this->describe(1));

        $this->assertSame(['column' => 90, 'display' => 'username'], $fields['user']['ui']);
        $this->assertSame('toOne', $fields['user']['kind']);
        $this->assertSame(['users'], $fields['user']['relationship']['types']);
        $this->assertSame('User', $fields['user']['label']);

        $this->assertSame(80, $fields['type']['ui']['column']);
        $this->assertSame('Type', $fields['type']['label']);
        $this->assertSame(75, $fields['title']['ui']['column']);
        $this->assertSame(70, $fields['device']['ui']['column']);
        $this->assertSame('Device', $fields['device']['label']);
        $this->assertSame(60, $fields['lastIpAddress']['ui']['column']);
        $this->assertSame('IP address', $fields['lastIpAddress']['label']);

        $this->assertSame('datetime', $fields['createdAt']['kind']);
        $this->assertTrue($fields['createdAt']['sortable']);
        $this->assertSame('Created', $fields['createdAt']['label']);
        $this->assertTrue($fields['lastActivityAt']['sortable']);
        $this->assertSame('Last active', $fields['lastActivityAt']['label']);
    }

    #[Test]
    public function the_token_value_is_never_described()
    {
        foreach ([1, 2, 3] as $actorId) {
            $this->assertArrayNotHasKey('token', $this->fieldsByName($this->describe($actorId)), "actor $actorId");
        }
    }

    #[Test]
    public function admin_and_moderator_get_the_user_and_type_filters()
    {
        foreach ([1, 3] as $actorId) {
            $filters = $this->filtersByKey($this->describe($actorId));

            $this->assertSame(['key' => 'user', 'kind' => 'relationship', 'related' => 'users'], $filters['user'], "actor $actorId");
            $this->assertSame('enum', $filters['type']['kind']);
            $this->assertEqualsCanonicalizing(['session', 'session_remember', 'developer'], $filters['type']['enum']);
        }
    }

    #[Test]
    public function member_gets_a_descriptor_scoped_to_their_own_tokens()
    {
        $descriptor = $this->describe(2);
        $filters = $this->filtersByKey($descriptor);

        // Members list their own tokens and may create developer tokens by default.
        $this->assertTrue($descriptor['capabilities']['list']);
        $this->assertArrayNotHasKey('user', $filters);
        $this->assertArrayHasKey('type', $filters);
    }

    #[Test]
    public function guests_cannot_read_the_descriptor()
    {
        $response = $this->send($this->request('GET', '/api/resource-schemas/access-tokens'));

        $this->assertEquals(401, $response->getStatusCode());
    }

    #[Test]
    public function filters_describe_themselves()
    {
        $this->app();

        $admin = User::find(1);
        $member = User::find(2);

        $this->assertSame(['kind' => 'relationship', 'related' => 'users'], (new UserFilter())->describe($admin));
        $this->assertNull((new UserFilter())->describe($member));

        $type = (new AccessTokenTypeFilter())->describe($member);
        $this->assertSame('enum', $type['kind']);
        $this->assertContains('developer', $type['enum']);
    }

    #[Test]
    public function the_list_includes_the_user_and_sorts_by_last_activity()
    {
        $response = $this->send(
            $this->request('GET', '/api/access-tokens', ['authenticatedAs' => 1])
                ->withQueryParams(['include' => 'user', 'sort' => '-lastActivityAt', 'filter' => ['user' => 2]])
        );

        $body = json_decode($contents = (string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode(), $contents);
        $this->assertSame(['2', '3'], Arr::pluck($body['data'], 'id'));
        $this->assertSame('session', $body['data'][0]['attributes']['type']);
        $this->assertSame(['type' => 'users', 'id' => '2'], $body['data'][0]['relationships']['user']['data']);
        $this->assertSame('normal', Arr::first($body['included'], fn ($r) => $r['type'] === 'users')['attributes']['username']);
        $this->assertArrayNotHasKey('token', $body['data'][0]['attributes']);
    }

    #[Test]
    public function can_delete_is_false_for_the_current_session_and_true_for_others()
    {
        $response = $this->send(
            $request = $this->request('GET', '/api/access-tokens', ['authenticatedAs' => 1])
        );

        $body = json_decode($contents = (string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode(), $contents);

        $byId = array_column($body['data'], 'attributes', 'id');

        $this->assertTrue($byId['1']['canDelete']);
        $this->assertTrue($byId['2']['canDelete']);

        // The request's own session token is current, and only logging out ends it.
        $testsToken = AccessToken::findValid($request->getAttribute('tests_token'));
        $this->assertTrue($byId[(string) $testsToken->id]['isCurrent']);
        $this->assertFalse($byId[(string) $testsToken->id]['canDelete']);
    }

    #[Test]
    public function member_can_delete_only_their_own_tokens()
    {
        $response = $this->send(
            $this->request('GET', '/api/access-tokens', ['authenticatedAs' => 2])
        );

        $body = json_decode($contents = (string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode(), $contents);

        foreach ($body['data'] as $token) {
            $this->assertSame(2, $token['attributes']['userId']);
            $this->assertSame(! $token['attributes']['isCurrent'], $token['attributes']['canDelete']);
        }
    }
}
