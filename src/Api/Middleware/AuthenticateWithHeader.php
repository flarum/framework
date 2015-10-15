<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Middleware;

use Flarum\Api\AccessToken;
use Flarum\Api\ApiKey;
use Flarum\Core\Guest;
use Flarum\Core\User;
use Flarum\Locale\LocaleManager;
use Illuminate\Contracts\Container\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Stratigility\MiddlewareInterface;

class AuthenticateWithHeader implements MiddlewareInterface
{
    /**
     * @var string
     */
    protected $prefix = 'Token ';

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
     * @param Request $request
     * @return Request
     */
    protected function logIn(Request $request)
    {
        $header = $request->getHeaderLine('authorization');

        $parts = explode(';', $header);

        $actor = new Guest;

        if (isset($parts[0]) && starts_with($parts[0], $this->prefix)) {
            $token = substr($parts[0], strlen($this->prefix));

            if (($accessToken = AccessToken::find($token)) && $accessToken->isValid()) {
                $actor = $accessToken->user;

                $actor->updateLastSeen()->save();
            } elseif (isset($parts[1]) && ($apiKey = ApiKey::valid($token))) {
                $userParts = explode('=', trim($parts[1]));

                if (isset($userParts[0]) && $userParts[0] === 'userId') {
                    $actor = User::find($userParts[1]);
                }
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

        return $request->withAttribute('actor', $actor ?: new Guest);
    }
}
