<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;

class CsrfTest extends TestCase
{
    protected $testUser = [
        'username' => 'test',
        'password' => 'too-obscure',
        'email' => 'test@machine.local',
    ];

    /**
     * @test
     */
    public function create_user_post_needs_csrf_token_by_default()
    {
        $response = $this->send(
            $this->request('POST', '/api/users', [
                'json' => [
                    'data' => [
                        'attributes' => $this->testUser
                    ]
                ],
            ])
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function create_user_post_doesnt_need_csrf_token_if_whitelisted()
    {
        $this->extend(
            (new Extend\Csrf)
                ->exemptRoute('users.create')
        );

        $response = $this->send(
            $this->request('POST', '/api/users', [
                'json' => [
                    'data' => [
                        'attributes' => $this->testUser
                    ]
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());

        $user = User::where('username', $this->testUser['username'])->firstOrFail();

        $this->assertEquals(0, $user->is_email_confirmed);
        $this->assertEquals($this->testUser['username'], $user->username);
        $this->assertEquals($this->testUser['email'], $user->email);
    }
}
