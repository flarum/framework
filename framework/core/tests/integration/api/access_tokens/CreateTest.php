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
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'normal3', 'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', 'email' => 'normal3@machine.local', 'is_email_confirmed' => 1]
            ],
            Group::class => [
                ['id' => 10, 'name_plural' => 'Acme', 'name_singular' => 'Acme']
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 10]
            ],
            'group_permission' => [
                ['permission' => 'createAccessToken', 'group_id' => 10]
            ],
        ]);
    }

    /**
     * @dataProvider canCreateTokens
     * @test
     */
    public function user_can_create_developer_tokens(int $authenticatedAs)
    {
        $response = $this->send(
            $this->request('POST', '/api/access-tokens', [
                'authenticatedAs' => $authenticatedAs,
                'json' => [
                    'data' => [
                        'type' => 'access-tokens',
                        'attributes' => [
                            'title' => 'Dev'
                        ]
                    ]
                ]
            ])
        );

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());
    }

    /**
     * @dataProvider cannotCreateTokens
     * @test
     */
    public function user_cannot_delete_other_users_tokens(int $authenticatedAs)
    {
        $response = $this->send(
            $this->request('POST', '/api/access-tokens', [
                'authenticatedAs' => $authenticatedAs,
                'json' => [
                    'data' => [
                        'type' => 'access-tokens',
                        'attributes' => [
                            'title' => 'Dev'
                        ]
                    ]
                ]
            ])
        );

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
    }

    /**
     * @test
     */
    public function user_cannot_create_token_without_title()
    {
        $response = $this->send(
            $this->request('POST', '/api/access-tokens', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'access-tokens',
                        'attributes' => []
                    ]
                ]
            ])
        );

        $this->assertEquals(422, $response->getStatusCode(), (string) $response->getBody());
    }

    public function canCreateTokens(): array
    {
        return [
            [1], // Admin
            [3], // User with permission
        ];
    }

    public function cannotCreateTokens(): array
    {
        return [
            [2]
        ];
    }
}
