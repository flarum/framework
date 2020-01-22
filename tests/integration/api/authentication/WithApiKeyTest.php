<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\authentication;

use Carbon\Carbon;
use Flarum\Api\ApiKey;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;
use Illuminate\Support\Str;

class WithApiKeyTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp()
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->adminUser(),
                $this->normalUser(),
            ],
            'api_keys' => [],
        ]);
    }

    protected function key(int $user_id = null): ApiKey
    {
        return ApiKey::unguarded(function () use ($user_id) {
            return ApiKey::query()->firstOrCreate([
                'key'        => Str::random(),
                'user_id'    => $user_id,
                'created_at' => Carbon::now()
            ]);
        });
    }

    /**
     * @test
     */
    public function cannot_authorize_without_key()
    {
        $response = $this->send(
            $this->request('GET', '/api')
        );

        $data = json_decode($response->getBody(), true);
        $this->assertFalse($data['data']['attributes']['canViewUserList']);
    }

    /**
     * @test
     */
    public function master_token_can_authenticate_as_anyone()
    {
        $key = $this->key();

        $response = $this->send(
            $this->request('GET', '/api')
                ->withAddedHeader('Authorization', "Token {$key->key}; userId=1")
        );

        $data = json_decode($response->getBody(), true);
        $this->assertTrue($data['data']['attributes']['canViewUserList']);
        $this->assertArrayHasKey('adminUrl', $data['data']['attributes']);

        $key->refresh();

        $this->assertNotNull($key->last_activity_at);
    }

    /**
     * @test
     */
    public function personal_api_token_cannot_authenticate_as_anyone()
    {
        $key = $this->key(2);

        $response = $this->send(
            $this->request('GET', '/api')
                ->withAddedHeader('Authorization', "Token {$key->key}; userId=1")
        );

        $data = json_decode($response->getBody(), true);
        $this->assertTrue($data['data']['attributes']['canViewUserList']);
        $this->assertArrayNotHasKey('adminUrl', $data['data']['attributes']);

        $key->refresh();

        $this->assertNotNull($key->last_activity_at);
    }

    /**
     * @test
     */
    public function personal_api_token_authenticates_user()
    {
        $key = $this->key(2);

        $response = $this->send(
            $this->request('GET', '/api')
                ->withAddedHeader('Authorization', "Token {$key->key}")
        );

        $data = json_decode($response->getBody(), true);
        $this->assertTrue($data['data']['attributes']['canViewUserList']);
        $this->assertArrayNotHasKey('adminUrl', $data['data']['attributes']);

        $key->refresh();

        $this->assertNotNull($key->last_activity_at);
    }
}
