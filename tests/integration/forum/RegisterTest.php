<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\forum;

use Flarum\Extend;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class RegisterTest extends TestCase
{
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->extend(
            (new Extend\Csrf)->exemptRoute('register')
        );
    }

    /**
     * @test
     */
    public function cant_register_without_data()
    {
        $response = $this->send(
            $this->request('POST', '/register')
        );

        $this->assertEquals(422, $response->getStatusCode());

        // The response body should contain details about the failed validation
        $body = (string) $response->getBody();
        $this->assertJson($body);
        $this->assertEquals([
            'errors' => [
                [
                    'status' => '422',
                    'code' => 'validation_error',
                    'detail' => 'The username field is required.',
                    'source' => ['pointer' => '/data/attributes/username'],
                ],
                [
                    'status' => '422',
                    'code' => 'validation_error',
                    'detail' => 'The email field is required.',
                    'source' => ['pointer' => '/data/attributes/email'],
                ],
                [
                    'status' => '422',
                    'code' => 'validation_error',
                    'detail' => 'The password field is required.',
                    'source' => ['pointer' => '/data/attributes/password'],
                ],
            ],
        ], json_decode($body, true));
    }

    /**
     * @test
     */
    public function can_register_with_data()
    {
        $response = $this->send(
            $this->request('POST', '/register', [
                'json' => [
                    'username' => 'test',
                    'password' => 'too-obscure',
                    'email' => 'test@machine.local',
                ]
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        /** @var User $user */
        $user = User::where('username', 'test')->firstOrFail();

        $this->assertEquals(0, $user->is_email_confirmed);
        $this->assertEquals('test', $user->username);
        $this->assertEquals('test@machine.local', $user->email);
    }
}
