<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\access_tokens;

use Flarum\Http\SessionConfig;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Cookies carry two separate decisions: how long the server considers a session
 * good for, and how long the browser agrees to keep presenting it. They are
 * configured independently because they answer different worries — one is about
 * session length, the other about walking away from a shared computer.
 */
class SessionCookieLifetimeTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    #[Test]
    public function sessions_last_two_hours_by_default()
    {
        $this->app();

        $this->assertEquals(120, $this->app()->getContainer()->make(SessionConfig::class)->lifetime());
    }

    #[Test]
    public function session_lifetime_can_be_set_in_config()
    {
        $this->config('session.lifetime', 30);

        $this->assertEquals(30, $this->app()->getContainer()->make(SessionConfig::class)->lifetime());
    }

    #[Test]
    public function session_lifetime_can_be_set_in_settings()
    {
        $this->setting('session.lifetime', 45);

        $this->assertEquals(45, $this->app()->getContainer()->make(SessionConfig::class)->lifetime());
    }

    #[Test]
    public function config_wins_over_settings_for_session_lifetime()
    {
        $this->setting('session.lifetime', 45);
        $this->config('session.lifetime', 30);

        $this->assertEquals(30, $this->app()->getContainer()->make(SessionConfig::class)->lifetime());
    }

    #[Test]
    public function an_unusable_session_lifetime_falls_back_to_the_default()
    {
        $this->config('session.lifetime', 0);

        $this->assertEquals(120, $this->app()->getContainer()->make(SessionConfig::class)->lifetime());
    }

    #[Test]
    public function cookies_outlive_the_browser_by_default()
    {
        $this->app();

        $this->assertFalse($this->app()->getContainer()->make(SessionConfig::class)->cookieExpiresOnClose());
    }

    #[Test]
    public function cookies_can_be_set_to_expire_when_the_browser_closes()
    {
        $this->config('session.cookie_expires_on_close', true);

        $this->assertTrue($this->app()->getContainer()->make(SessionConfig::class)->cookieExpiresOnClose());
    }

    #[Test]
    public function expiring_on_close_can_be_set_in_settings()
    {
        $this->setting('session.cookie_expires_on_close', true);

        $this->assertTrue($this->app()->getContainer()->make(SessionConfig::class)->cookieExpiresOnClose());
    }

    #[Test]
    public function the_session_cookie_carries_the_configured_lifetime()
    {
        $this->config('session.lifetime', 30);

        $response = $this->send($this->request('GET', '/'));

        $cookie = $this->sessionCookie($response);

        // Max-Age is in seconds; the setting is in minutes.
        $this->assertStringContainsString('Max-Age=1800', $cookie);
    }

    #[Test]
    public function the_session_cookie_becomes_a_browser_session_cookie_when_configured()
    {
        $this->config('session.cookie_expires_on_close', true);

        $response = $this->send($this->request('GET', '/'));

        $cookie = $this->sessionCookie($response);

        // No Max-Age and no Expires is what makes a browser drop a cookie when
        // it closes, so their absence is the assertion.
        $this->assertStringNotContainsString('Max-Age=', $cookie);
        $this->assertStringNotContainsString('Expires=', $cookie);
    }

    #[Test]
    public function config_pins_the_settings_out_of_reach()
    {
        $this->config('session.lifetime', 30);

        $this->assertTrue($this->app()->getContainer()->make(SessionConfig::class)->configOverride());
    }

    #[Test]
    public function nothing_is_pinned_when_config_says_nothing()
    {
        $this->app();

        $this->assertFalse($this->app()->getContainer()->make(SessionConfig::class)->configOverride());
    }

    protected function sessionCookie($response): string
    {
        foreach ($response->getHeader('Set-Cookie') as $cookie) {
            if (str_starts_with($cookie, 'flarum_session=')) {
                return $cookie;
            }
        }

        $this->fail('No session cookie was set on the response.');
    }
}
