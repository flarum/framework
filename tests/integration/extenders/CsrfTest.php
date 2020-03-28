<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;
use Flarum\User\User;

class CsrfTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @test
     */
    public function create_user_post_needs_csrf_token_by_default()
    {
        $response = $this->send(
            $this->request('POST', '/api/users', [
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
            ])
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function create_user_post_doesnt_csrf_token_if_whitelisted()
    {
        $this->extend(
            (new Extend\Csrf)
                ->exemptPath('/api/users')
        );

        $response = $this->send(
            $this->request('POST', '/api/users', [
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
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $user = User::where('username', 'test')->firstOrFail();

        $this->assertEquals(0, $user->is_email_confirmed);
        $this->assertEquals('test', $user->username);
        $this->assertEquals('test@machine.local', $user->email);
    }
}
