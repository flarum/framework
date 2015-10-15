<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Flarum\Api\AccessToken;
use Flarum\Core\Guest;
use Flarum\Locale\LocaleManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Stratigility\MiddlewareInterface;

class AuthenticateWithCookie implements MiddlewareInterface
{
    /**
     * @var LocaleManager
     */
    protected $locales;

    /**
     * @param LocaleManager $locales
     */
    public function __construct(LocaleManager $locales)
    {
        $this->locales = $locales;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        $request = $this->logIn($request);

        return $out ? $out($request, $response) : $response;
    }

    /**
     * Set the application's actor instance according to the request token.
     *
     * @param Request $request
     * @return Request
     */
    protected function logIn(Request $request)
    {
        $actor = new Guest;

        if ($token = $this->getToken($request)) {
            if (! $token->isValid()) {
                // TODO: https://github.com/flarum/core/issues/253
            } elseif ($actor = $token->user) {
                $actor->updateLastSeen()->save();
            }
        }

        if ($actor->exists) {
            $locale = $actor->getPreference('locale');
        } else {
            $locale = array_get($request->getCookieParams(), 'locale');
        }

        if ($locale && $this->locales->hasLocale($locale)) {
            $this->locales->setLocale($locale);
        }

        return $request->withAttribute('actor', $actor);
    }

    /**
     * Get the access token referred to by the request cookie.
     *
     * @param Request $request
     * @return AccessToken|null
     */
    protected function getToken(Request $request)
    {
        $token = array_get($request->getCookieParams(), 'flarum_remember');

        if ($token) {
            return AccessToken::find($token);
        }
    }
}
