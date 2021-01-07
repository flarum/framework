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

class CreateTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'settings' => [
                ['key' => 'mail_driver', 'value' => 'log'],
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
                'POST',
                '/api/users',
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
                'POST',
                '/api/users',
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

        $this->assertEquals(0, $user->is_email_confirmed);
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
                'POST',
                '/api/users',
                [
                    'authenticatedAs' => 1,
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
            )
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
        $settings = $this->app()->getContainer()->make(SettingsRepositoryInterface::class);
        $settings->set('allow_sign_up', false);

        $response = $this->send(
            $this->request(
                'POST',
                '/api/users',
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
