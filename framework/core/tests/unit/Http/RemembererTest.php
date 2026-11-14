<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Http;

use Dflydev\FigCookies\FigResponseCookies;
use Flarum\Foundation\Config;
use Flarum\Http\CookieFactory;
use Flarum\Http\RememberAccessToken;
use Flarum\Http\Rememberer;
use Flarum\Http\SessionConfig;
use Flarum\Settings\UninstalledSettingsRepository;
use Flarum\Testing\unit\TestCase;
use Illuminate\Container\Container;
use Laminas\Diactoros\Response\EmptyResponse;
use PHPUnit\Framework\Attributes\Test;

class RemembererTest extends TestCase
{
    protected ?Container $previousContainer = null;

    protected function tearDown(): void
    {
        if ($this->previousContainer) {
            Container::setInstance($this->previousContainer);
        }

        parent::tearDown();
    }

    protected function rememberer(array $session = []): Rememberer
    {
        $config = new Config(['url' => 'http://flarum.test', 'session' => $session]);
        $sessionConfig = new SessionConfig($config, new UninstalledSettingsRepository());

        // Token lifetimes are resolved through the container.
        $this->previousContainer = Container::getInstance();
        $container = new Container();
        $container->instance(SessionConfig::class, $sessionConfig);
        Container::setInstance($container);

        return new Rememberer(new CookieFactory($config), $sessionConfig);
    }

    protected function token(string $class): RememberAccessToken
    {
        $token = new $class;
        $token->token = 'a-remember-token';

        return $token;
    }

    #[Test]
    public function remember_cookie_uses_the_default_token_lifetime()
    {
        $response = $this->rememberer()->remember(
            new EmptyResponse(),
            $this->token(RememberAccessToken::class)
        );

        $cookie = FigResponseCookies::get($response, 'flarum_remember');

        $this->assertEquals('a-remember-token', $cookie->getValue());
        $this->assertEquals(5 * 365 * 24 * 60 * 60, $cookie->getMaxAge());
    }

    #[Test]
    public function remember_cookie_uses_a_subclass_lifetime()
    {
        $response = $this->rememberer()->remember(
            new EmptyResponse(),
            $this->token(ShortLivedRememberAccessToken::class)
        );

        $this->assertEquals(
            7 * 24 * 60 * 60,
            FigResponseCookies::get($response, 'flarum_remember')->getMaxAge()
        );
    }

    #[Test]
    public function remember_cookie_uses_the_configured_lifetime_of_a_subclass()
    {
        $response = $this->rememberer(['tokens' => ['session_remember_short' => 3600]])->remember(
            new EmptyResponse(),
            $this->token(ShortLivedRememberAccessToken::class)
        );

        $this->assertEquals(3600, FigResponseCookies::get($response, 'flarum_remember')->getMaxAge());
    }

    #[Test]
    public function remember_cookie_expires_on_close_when_configured()
    {
        $response = $this->rememberer(['cookie_expires_on_close' => true])->remember(
            new EmptyResponse(),
            $this->token(ShortLivedRememberAccessToken::class)
        );

        $this->assertEquals(0, FigResponseCookies::get($response, 'flarum_remember')->getMaxAge());
    }

    #[Test]
    public function remember_cookie_outlives_the_browser_for_a_token_that_never_expires()
    {
        $response = $this->rememberer()->remember(
            new EmptyResponse(),
            $this->token(NeverExpiringRememberAccessToken::class)
        );

        $this->assertEquals(
            5 * 365 * 24 * 60 * 60,
            FigResponseCookies::get($response, 'flarum_remember')->getMaxAge()
        );
    }

    #[Test]
    public function remember_cookie_outlives_the_browser_when_the_site_configures_no_expiry()
    {
        $response = $this->rememberer(['tokens' => ['session_remember' => 0]])->remember(
            new EmptyResponse(),
            $this->token(RememberAccessToken::class)
        );

        $this->assertEquals(
            5 * 365 * 24 * 60 * 60,
            FigResponseCookies::get($response, 'flarum_remember')->getMaxAge()
        );
    }
}

class NeverExpiringRememberAccessToken extends RememberAccessToken
{
    public static string $type = 'session_remember_forever';

    protected static int $lifetime = 0;
}

class ShortLivedRememberAccessToken extends RememberAccessToken
{
    public static string $type = 'session_remember_short';

    protected static int $lifetime = 7 * 24 * 60 * 60;
}
