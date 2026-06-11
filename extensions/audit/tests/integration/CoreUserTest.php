<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Tests\integration;

use Flarum\Group\Group;
use Flarum\User\EmailToken;
use Flarum\User\PasswordToken;
use Illuminate\Support\Arr;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\UploadedFile;

class CoreUserTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                [
                    'id' => 3,
                    'username' => 'user3',
                    'email' => 'user3@example.com',
                    'is_email_confirmed' => true,
                    'password' => '$2y$04$3gJO9MUZIyJbOxMbrW2JW.qa8EDxGXSqjYYI7KWZyyg0j6169Udfu', // "secret"
                ],
                [
                    'id' => 4,
                    'username' => 'user4',
                    'email' => 'user4@example.com',
                    'is_email_confirmed' => false,
                    'password' => '',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function passwordChangeRequested()
    {
        $this->sendSuccessfulRequest('POST', '/api/forgot', [
            'json' => [
                'email' => 'user3@example.com',
            ],
        ], 204);

        $this->assertLogExists('user.password_change_requested', [
            'user_id' => 3,
        ]);
    }

    /**
     * @test
     */
    public function activatedAdmin()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/users/4', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'isEmailConfirmed' => true,
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('user.activated', [
            'user_id' => 4,
        ]);
    }

    /**
     * @test
     */
    public function activatedUser()
    {
        $this->app(); // Initialize app for query below

        $token = EmailToken::generate('user4@example.com', 4);
        $token->save();

        $this->sendForumCsrfRequest('POST', '/confirm/'.$token->token, [], 302);

        $this->assertLogExists('user.activated_with_email', [
            'user_id' => 4,
        ], null);

        $this->assertLogDoesntExist('user.activated');
        $this->assertLogDoesntExist('user.email_changed');
    }

    /**
     * @test
     */
    public function avatarChanged()
    {
        // We build our own request instead of using ->withUploadedFiles on the ->request() helper
        // because for some reason withUploadedFiles expects an array of arrays
        // while ServerRequestFactory::fromGlobals and Flarum code expects a flat array
        $response = $this->send($this->requestAsUser(new ServerRequest([], [
            'avatar' => new UploadedFile(new Stream(__DIR__.'/../fixtures/16x16.png'), 83, 0, '16x16.png', 'image/png'),
        ], '/api/users/3/avatar', 'POST'), 1));

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertLogExists('user.avatar_changed', [
            'user_id' => 3,
        ]);
    }

    /**
     * @test
     */
    public function avatarRemoved()
    {
        $this->sendSuccessfulRequest('DELETE', '/api/users/3/avatar', []);

        $this->assertLogExists('user.avatar_removed', [
            'user_id' => 3,
        ]);
    }

    /**
     * @test
     */
    public function deleted()
    {
        $this->sendSuccessfulRequest('DELETE', '/api/users/3', [], 204);

        $this->assertLogExists('user.deleted', [
            'user_id' => 3,
        ]);
    }

    /**
     * @test
     */
    public function emailChangeRequested()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/users/3', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'email' => 'user33@example.com',
                    ],
                ],
                'meta' => [
                    'password' => 'secret',
                ],
            ],
        ], 200, 3);

        $this->assertLogExists('user.email_change_requested', [
            'user_id' => 3,
            'new_email' => 'user33@example.com',
        ], 3);
    }

    /**
     * @test
     */
    public function emailChangedAdmin()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/users/3', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'email' => 'user33@example.com',
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('user.email_changed', [
            'user_id' => 3,
            'old_email' => 'user3@example.com',
            'new_email' => 'user33@example.com',
        ]);
    }

    /**
     * @test
     */
    public function emailChangedUser()
    {
        $this->app(); // Initialize app for query below

        $token = EmailToken::generate('user33@example.com', 3);
        $token->save();

        $this->sendForumCsrfRequest('POST', '/confirm/'.$token->token, [], 302);

        $this->assertLogExists('user.email_changed', [
            'user_id' => 3,
            'old_email' => 'user3@example.com',
            'new_email' => 'user33@example.com',
        ], null);

        $this->assertLogDoesntExist('user.activated');

        // Flarum triggers the Activated event again every time the email is confirmed
        // which is undesirable for our logs https://github.com/flarum/core/issues/2713
        // TODO: when fixed in core, uncomment this
        // $this->assertLogDoesntExist('user.activated_with_email');
    }

    /**
     * @test
     */
    public function groupsChanged()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/users/3', [
            'json' => [
                'data' => [
                    'relationships' => [
                        'groups' => [
                            'data' => [
                                [
                                    'type' => 'groups',
                                    'id' => Group::MODERATOR_ID,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('user.groups_changed', [
            'user_id' => 3,
            'old_group_ids' => [],
            'new_group_ids' => [Group::MODERATOR_ID],
        ]);
    }

    /**
     * @test
     */
    public function loginAndLogout()
    {
        $response = $this->sendForumCsrfRequest('POST', '/login', [
            'json' => [
                'identification' => 'user3',
                'password' => 'secret',
            ],
        ]);

        $this->assertLogExists('user.logged_in', [
            'user_id' => 3,
        ], 3);

        $this->assertLogDoesntExist('user.logged_out');

        $response = $this->send($this->request('GET', '/logout', [
            'cookiesFrom' => $response,
        ])->withQueryParams([
            'token' => $response->getHeaderLine('X-CSRF-Token'),
        ]));

        $this->assertEquals(302, $response->getStatusCode(), 'Asserting logout status code');

        $this->assertLogExists('user.logged_out', [
            'user_id' => 3,
        ], 3);
    }

    /**
     * @test
     */
    public function passwordChangedAdmin()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/users/3', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'password' => '12345678',
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('user.password_changed', [
            'user_id' => 3,
        ]);
    }

    /**
     * @test
     */
    public function passwordChangedUser()
    {
        $this->app(); // Initialize app for query below

        $token = PasswordToken::generate(3);
        $token->save();

        $this->sendForumCsrfRequest('POST', '/reset', [
            'json' => [
                'passwordToken' => $token->token,
                'password' => '12345678',
                'password_confirmation' => '12345678',
            ],
        ], 302);

        $this->assertLogExists('user.password_changed', [
            'user_id' => 3,
        ], null);
    }

    /**
     * @test
     */
    public function registeredAdmin()
    {
        $response = $this->sendSuccessfulRequest('POST', '/api/users', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'username' => 'user5',
                        'email' => 'user5@example.com',
                        'password' => '12345678',
                    ],
                ],
            ],
        ], 201);

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertLogExists('user.created', [
            'user_id' => Arr::get($body, 'data.id'),
        ]);

        $this->assertLogDoesntExist('user.logged_in');
    }

    /**
     * @test
     */
    public function registeredUser()
    {
        $response = $this->sendForumCsrfRequest('POST', '/register', [
            'json' => [
                'username' => 'user5',
                'email' => 'user5@example.com',
                'password' => '12345678',
            ],
        ], 201);

        // The RegisterController does not rewind the body
        $response->getBody()->rewind();

        $body = json_decode($response->getBody()->getContents(), true);

        $this->assertLogExists('user.created', [
            'user_id' => Arr::get($body, 'data.id'),
        ], null);

        $this->assertLogDoesntExist('user.logged_in');
    }

    /**
     * @test
     */
    public function renamed()
    {
        $this->sendSuccessfulRequest('PATCH', '/api/users/3', [
            'json' => [
                'data' => [
                    'attributes' => [
                        'username' => 'user33',
                    ],
                ],
            ],
        ]);

        $this->assertLogExists('user.username_changed', [
            'user_id' => 3,
            'old_username' => 'user3',
            'new_username' => 'user33',
        ]);
    }
}
