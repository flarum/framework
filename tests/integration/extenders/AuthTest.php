<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Forum\Auth\SsoDriverInterface;
use Flarum\Forum\Auth\SsoResponse;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tests\integration\AuthenticatedTestCase;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthTest extends AuthenticatedTestCase
{
    protected function settings()
    {
        return $this->app()->getContainer()->make(SettingsRepositoryInterface::class);
    }

    protected function enableProvider($provider)
    {
        $this->settings()->set('auth_driver_enabled_'.$provider, true);
    }

    /**
     * @test
     */
    public function sso_provider_doesnt_exist_if_not_registered()
    {
        $response = $this->send(
            $this->request('GET', '/auth/custom_sso')
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function sso_provider_doesnt_exist_if_registered_but_not_enabled()
    {
        $this->extend((new Extend\Auth)->ssoDriver('custom_sso', CustomSsoProvider::class));

        $response = $this->send(
            $this->request('GET', '/auth/custom_sso')
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function sso_driver_can_return_http_redirect()
    {
        $this->extend((new Extend\Auth)->ssoDriver('custom_sso', CustomSsoProvider::class));

        $this->enableProvider('custom_sso');

        $response = $this->send(
            $this->request('GET', '/auth/custom_sso')->withAttribute('redirect', true)
        );

        $this->assertEquals(302, $response->getStatusCode());
    }
}

class CustomSsoProvider implements SsoDriverInterface
{
    public function meta(): array
    {
        return [];
    }

    public function sso(Request $request, SsoResponse $response)
    {
        if ($request->getAttribute('redirect')) {
            return new RedirectResponse('https://example.com');
        }
    }
}