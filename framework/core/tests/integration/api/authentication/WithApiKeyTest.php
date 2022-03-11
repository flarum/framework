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
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

class WithApiKeyTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
            'api_keys' => [
                ['key' => 'mastertoken', 'user_id' => null, 'created_at' => Carbon::now()->toDateTimeString()],
                ['key' => 'personaltoken', 'user_id' => 2, 'created_at' => Carbon::now()->toDateTimeString()],
            ]
        ]);
    }

    /**
     * @test
     */
    public function cannot_authorize_without_key()
    {
        $response = $this->send(
            $this->request('GET', '/api')
        );

        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertFalse($data['data']['attributes']['canSearchUsers']);
    }

    /**
     * @test
     */
    public function master_token_can_authenticate_as_anyone()
    {
        $response = $this->send(
            $this->request('GET', '/api')
                ->withAddedHeader('Authorization', 'Token mastertoken; userId=1')
        );

        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($data['data']['attributes']['canSearchUsers']);
        $this->assertArrayHasKey('adminUrl', $data['data']['attributes']);

        $key = ApiKey::where('key', 'mastertoken')->first();

        $this->assertNotNull($key->last_activity_at);
    }

    /**
     * @test
     */
    public function personal_api_token_cannot_authenticate_as_anyone()
    {
        $response = $this->send(
            $this->request('GET', '/api')
                ->withAddedHeader('Authorization', 'Token personaltoken; userId=1')
        );

        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($data['data']['attributes']['canSearchUsers']);
        $this->assertArrayNotHasKey('adminUrl', $data['data']['attributes']);

        $key = ApiKey::where('key', 'personaltoken')->first();

        $this->assertNotNull($key->last_activity_at);
    }

    /**
     * @test
     */
    public function personal_api_token_authenticates_user()
    {
        $response = $this->send(
            $this->request('GET', '/api')
                ->withAddedHeader('Authorization', 'Token personaltoken')
        );

        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertTrue($data['data']['attributes']['canSearchUsers']);
        $this->assertArrayNotHasKey('adminUrl', $data['data']['attributes']);

        $key = ApiKey::where('key', 'personaltoken')->first();

        $this->assertNotNull($key->last_activity_at);
    }
}
