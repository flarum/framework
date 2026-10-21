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
use Flarum\Testing\unit\TestCase;
use Laminas\Diactoros\Response\EmptyResponse;
use PHPUnit\Framework\Attributes\Test;

class RemembererTest extends TestCase
{
    protected function rememberer(): Rememberer
    {
        return new Rememberer(new CookieFactory(new Config(['url' => 'http://flarum.test'])));
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
}

class ShortLivedRememberAccessToken extends RememberAccessToken
{
    public static string $type = 'session_remember_short';

    protected static int $lifetime = 7 * 24 * 60 * 60;
}
