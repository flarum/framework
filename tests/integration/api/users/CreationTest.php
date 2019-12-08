<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\users;

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;
use Flarum\User\User;

class CreationTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    public function setUp()
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->adminUser(),
            ],
            'groups' => [
                $this->adminGroup(),
            ],
            'group_user' => [
                ['user_id' => 1, 'group_id' => 1],
            ],
            'settings' => [
                ['key' => 'mail_driver', 'value' => 'log'],
            ],
            'access_tokens' => [
                ['token' => 'admintoken', 'user_id' => 1],
            ],
        ]);
    }

    /**
     * @test
     */
    public function cannot_create_user_without_data()
    {
        $response = $this->send(
            $this->request(
                'POST', '/api/users',
                [
                    'json' => ['data' => ['attributes' => []]],
                ]
            )->withAttribute('bypassCsrfToken', true)
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
                    'detail' => 'validation.required',
                    'source' => ['pointer' => '/data/attributes/username'],
                ],
                [
                    'status' => '422',
                    'code' => 'validation_error',
                    'detail' => 'validation.required',
                    'source' => ['pointer' => '/data/attributes/email'],
                ],
                [
                    'status' => '422',
                    'code' => 'validation_error',
                    'detail' => 'validation.required',
                    'source' => ['pointer' => '/data/attributes/password'],
                ],
            ],
        ], json_decode($body, true));
    }

    /**
     * @test
     */
    public function can_create_user()
    {
        $response = $this->send(
            $this->request(
                'POST', '/api/users',
                [
                    'json' => [
                        'data' => [
                            'attributes' => [
                                'username' => 'test',
                                'password' => 'too-obscure',
                                'email' => 'test@machine.local',
                            ],
                        ]
                    ],
                ]
            )->withAttribute('bypassCsrfToken', true)
        );

        $this->assertEquals(201, $response->getStatusCode());

        /** @var User $user */
        $user = User::where('username', 'test')->firstOrFail();

        $this->assertEquals(0, $user->is_activated);
        $this->assertEquals('test', $user->username);
        $this->assertEquals('test@machine.local', $user->email);
    }

    /**
     * @test
     */
    public function admins_can_create_activated_users()
    {
        $response = $this->send(
            $this->request(
                'POST', '/api/users',
                [
                    'json' => [
                        'data' => [
                            'attributes' => [
                                'username' => 'test',
                                'password' => 'too-obscure',
                                'email' => 'test@machine.local',
                                'isEmailConfirmed' => 1,
                            ],
                        ]
                    ],
                ]
            )->withHeader('Authorization', 'Token admintoken')
        );

        $this->assertEquals(201, $response->getStatusCode());

        /** @var User $user */
        $user = User::where('username', 'test')->firstOrFail();

        $this->assertEquals(1, $user->is_email_confirmed);
    }

    /**
     * @test
     */
    public function disabling_sign_up_prevents_user_creation()
    {
        /** @var SettingsRepositoryInterface $settings */
        $settings = app(SettingsRepositoryInterface::class);
        $settings->set('allow_sign_up', false);

        $response = $this->send(
            $this->request(
                'POST', '/api/users',
                [
                    'json' => [
                        'data' => [
                            'attributes' => [
                                'username' => 'test',
                                'password' => 'too-obscure',
                                'email' => 'test@machine.local',
                            ],
                        ]
                    ],
                ]
            )->withAttribute('bypassCsrfToken', true)
        );
        $this->assertEquals(403, $response->getStatusCode());

        $settings->set('allow_sign_up', true);
    }
}
