<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\access_tokens;

use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class PermissionTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'moderator1', 'email' => 'moderator1@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => 100, 'name_singular' => 'test', 'name_plural' => 'test']
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 100]
            ],
            'group_permission' => [
                ['group_id' => 100, 'permission' => 'moderateAccessTokens']
            ]
        ]);
    }

    #[Test]
    #[DataProvider('usersWithPermissionDataProvider')]
    public function user_with_permission_has_access(int $authenticatedAs): void
    {
        $response = $this->send(
            $this->request('GET', '/api', compact('authenticatedAs'))
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);

        $canModerateAccessTokens = Arr::get($body, 'data.attributes.canModerateAccessTokens');

        $this->assertNotNull($canModerateAccessTokens);
        $this->assertTrue($canModerateAccessTokens);
    }

    #[Test]
    #[DataProvider('usersWithoutPermissionDataProvider')]
    public function user_without_permission_has_no_access(?int $authenticatedAs): void
    {
        $response = $this->send(
            $this->request('GET', '/api', compact('authenticatedAs'))
        );

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);

        $canModerateAccessTokens = Arr::get($body, 'data.attributes.canModerateAccessTokens');

        $this->assertNotNull($canModerateAccessTokens);
        $this->assertFalse($canModerateAccessTokens);
    }

    public static function usersWithPermissionDataProvider(): array
    {
        return [
            [1],
            [3]
        ];
    }

    public static function usersWithoutPermissionDataProvider(): array
    {
        return [
            [2],
            [null]
        ];
    }
}
