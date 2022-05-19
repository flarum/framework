<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Nicknames\Tests\integration;

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
        $this->extension('flarum-nicknames');
        $this->extend(
            (new Extend\Csrf)->exemptRoute('register')
        );
    }

    /**
     * @test
     */
    public function can_register_with_nickname()
    {
        $this->setting('flarum-nicknames.set_on_registration', true);

        $response = $this->send(
            $this->request('POST', '/register', [
                'json' => [
                    'nickname' => 'фларум',
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

    /**
     * @test
     */
    public function cant_register_with_nickname_if_not_allowed()
    {
        $this->setting('flarum-nicknames.set_on_registration', false);

        $response = $this->send(
            $this->request('POST', '/register', [
                'json' => [
                    'nickname' => 'фларум',
                    'username' => 'test',
                    'password' => 'too-obscure',
                    'email' => 'test@machine.local',
                ]
            ])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function cant_register_with_nickname_if_invalid_regex()
    {
        $this->setting('flarum-nicknames.set_on_registration', true);
        $this->setting('flarum-nicknames.regex', '^[A-z]+$');

        $response = $this->send(
            $this->request('POST', '/register', [
                'json' => [
                    'nickname' => '007',
                    'username' => 'test',
                    'password' => 'too-obscure',
                    'email' => 'test@machine.local',
                ]
            ])
        );

        $this->assertEquals(422, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function can_register_with_nickname_if_valid_regex()
    {
        $this->setting('flarum-nicknames.set_on_registration', true);
        $this->setting('flarum-nicknames.regex', '^[A-z]+$');

        $response = $this->send(
            $this->request('POST', '/register', [
                'json' => [
                    'nickname' => 'Acme',
                    'username' => 'test',
                    'password' => 'too-obscure',
                    'email' => 'test@machine.local',
                ]
            ])
        );

        $this->assertEquals(201, $response->getStatusCode());
    }
}
