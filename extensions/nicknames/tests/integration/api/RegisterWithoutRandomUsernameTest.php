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

class RegisterWithoutRandomUsernameTest extends TestCase
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
        // Explicitly disable random usernames (this is the default)
        $this->setting('flarum-nicknames.random_username', false);
    }

    #[Test]
    public function username_required_when_randomization_disabled()
    {
        // Try to register without username when randomization is disabled
        $response = $this->send(
            $this->request('POST', '/api/users', [
                'json' => [
                    'data' => [
                        'attributes' => [
                            // No username sent
                            'nickname' => 'John Doe',
                            'email' => 'john@example.com',
                            'password' => 'secure_password_123',
                        ],
                    ],
                ],
            ])
        );

        // Should fail because username is required
        $this->assertEquals(422, $response->getStatusCode());
        $body = $response->getBody()->getContents();
        $this->assertStringContainsString('username', $body);
    }

    #[Test]
    public function normal_registration_still_works()
    {
        // Normal registration with username and nickname
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

        // Should use the provided username
        $this->assertEquals('johndoe', $user->username);
        $this->assertEquals('John Doe', $user->nickname);
    }
}
