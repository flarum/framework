<?php


namespace Flarum\Tests\unit\Http;

use Flarum\Foundation\Config;
use Flarum\Http\CookieFactory;
use Flarum\Tests\unit\TestCase;

class CookieFactoryTest extends TestCase
{
    protected function factory(array $config = null): CookieFactory
    {
        $config = new Config(array_merge([
            'url' => 'http://flarum.test'
        ], $config ?? []));

        return new CookieFactory($config);
    }

    /** @test */
    public function can_create_cookies()
    {
        $cookie = $this->factory()->make('test', 'australia');

        $this->assertEquals('flarum_test', $cookie->getName());
        $this->assertEquals('australia', $cookie->getValue());
        $this->assertEquals(0, $cookie->expiresAt());
        $this->assertFalse($cookie->isSecure());
        $this->assertEquals('/', $cookie->getPath());
    }

    /** @test */
    public function can_override_cookie_settings_from_config()
    {
        $cookie = $this->factory([
            'cookie' => [
                'name' => 'australia',
                'secure' => true,
                'domain' => 'flarum.com',
                'samesite' => 'none'
            ]
        ])->make('test', 'australia');

        $this->assertEquals('australia_test', $cookie->getName());
        $this->assertTrue($cookie->isSecure());
        $this->assertEquals('flarum.com', $cookie->getDomain());
        $this->assertEquals('none', $cookie->getSameSite());
    }
}
