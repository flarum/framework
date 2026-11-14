<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\forum;

use Flarum\Extend;
use Flarum\Http\AccessToken;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class LoginTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->extend(
            (new Extend\Csrf)->exemptRoute('login')
        );

        $this->prepareDatabase([
            User::class => [
                $this->normalUser()
            ]
        ]);
    }

    #[Test]
    public function cant_login_without_data()
    {
        $response = $this->send(
            $this->request('POST', '/login', [
                'json' => []
            ])
        );

        $this->assertEquals(401, $response->getStatusCode());
    }

    #[Test]
    public function cant_login_with_wrong_password()
    {
        $response = $this->send(
            $this->request('POST', '/login', [
                'json' => [
                    'identification' => 'normal',
                    'password' => 'incorrect'
                ]
            ])
        );

        $this->assertEquals(401, $response->getStatusCode());
    }

    #[Test]
    public function can_login_with_data()
    {
        $response = $this->send(
            $this->request('POST', '/login', [
                'json' => [
                    'identification' => 'normal',
                    'password' => 'too-obscure'
                ]
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        // The response body should contain the user ID...
        $body = (string) $response->getBody();
        $this->assertJson($body);

        $data = json_decode($body, true);
        $this->assertEquals(2, $data['userId']);

        // ...and an access token belonging to this user.
        $token = $data['token'];
        $this->assertEquals(2, AccessToken::whereToken($token)->firstOrFail()->user_id);
    }

    #[Test]
    public function login_with_remember_sets_remember_cookie()
    {
        $response = $this->send(
            $this->request('POST', '/login', [
                'json' => [
                    'identification' => 'normal',
                    'password' => 'too-obscure',
                    'remember' => true
                ]
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('flarum_remember=', $response->getHeaderLine('Set-Cookie'));
    }

    #[Test]
    public function login_form_with_remember_sets_remember_cookie_on_redirect()
    {
        $response = $this->send($this->formRequest(['remember' => '1']));

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('flarum_remember=', $response->getHeaderLine('Set-Cookie'));
    }

    #[Test]
    public function login_form_without_remember_sets_no_remember_cookie()
    {
        $response = $this->send($this->formRequest());

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringNotContainsString('flarum_remember=', $response->getHeaderLine('Set-Cookie'));
    }

    /**
     * A log in submitted by the maintenance mode log in page's HTML form.
     */
    protected function formRequest(array $body = [])
    {
        return $this->request('POST', '/login')
            ->withHeader('Accept', 'text/html')
            ->withParsedBody(array_merge(['identification' => 'normal', 'password' => 'too-obscure'], $body));
    }
}
