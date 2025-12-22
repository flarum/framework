<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Nicknames\Tests\Integration\Api;

use Flarum\Extend;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class RegisterWithRandomUsernameTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-nicknames');

        $this->extend(
            (new Extend\Csrf)->exemptRoute('users.create')
        );

        $this->prepareDatabase([
            User::class => [
                $this->normalUser()
            ],
        ]);

        $this->setting('display_name_driver', 'nickname');
        $this->setting('flarum-nicknames.set_on_registration', true);
        $this->setting('flarum-nicknames.random_username', true);
    }

    #[Test]
    public function can_register_with_nickname_and_random_username()
    {
        $response = $this->send(
            $this->request('POST', '/api/users', [
                'json' => [
                    'data' => [
                        'attributes' => [
                            // No username sent - backend will generate it automatically
                            'nickname' => 'John Doe',
                            'email' => 'john@example.com',
                            'password' => 'secure_password_123',
                        ],
                    ],
                ],
            ])
        );

        $body = $response->getBody()->getContents();

        $this->assertEquals(201, $response->getStatusCode());

        $data = json_decode($body, true)['data'];
        $user = User::find($data['id']);

        // Verify username was auto-generated
        $this->assertMatchesRegularExpression('/^user_[a-f0-9]{8}$/', $user->username);
        $this->assertEquals('John Doe', $user->nickname);
    }

    #[Test]
    public function can_provide_manual_username_when_randomization_enabled()
    {
        $response = $this->send(
            $this->request('POST', '/api/users', [
                'json' => [
                    'data' => [
                        'attributes' => [
                            'username' => 'johndoe',
                            'nickname' => 'John Doe',
                            'email' => 'john@example.com',
                            'password' => 'secure_password_123',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $user = User::find(json_decode($response->getBody()->getContents(), true)['data']['id']);

        // Should use provided username
        $this->assertEquals('johndoe', $user->username);
    }

    #[Test]
    public function generated_usernames_are_unique()
    {
        $usernames = [];

        for ($i = 0; $i < 5; $i++) {
            $response = $this->send(
                $this->request('POST', '/api/users', [
                    'json' => [
                        'data' => [
                            'attributes' => [
                                // No username sent
                                'nickname' => "User $i",
                                'email' => "user$i@example.com",
                                'password' => 'secure_password_123',
                            ],
                        ],
                    ],
                ])
            );

            $user = User::find(json_decode($response->getBody()->getContents(), true)['data']['id']);
            $usernames[] = $user->username;
        }

        $this->assertEquals(5, count(array_unique($usernames)));
    }

    #[Test]
    public function username_validation_still_works_when_randomization_enabled()
    {
        // Try to register with an invalid username (too short)
        $response = $this->send(
            $this->request('POST', '/api/users', [
                'json' => [
                    'data' => [
                        'attributes' => [
                            'username' => 'ab', // Too short (min is 3)
                            'nickname' => 'Test User',
                            'email' => 'test@example.com',
                            'password' => 'secure_password_123',
                        ],
                    ],
                ],
            ])
        );

        $this->assertEquals(422, $response->getStatusCode());
    }
}
