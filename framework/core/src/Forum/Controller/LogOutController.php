<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Http\Rememberer;
use Flarum\Http\RequestUtil;
use Flarum\Http\ReturnUrlValidator;
use Flarum\Http\SessionAuthenticator;
use Flarum\Http\UrlGenerator;
use Flarum\User\Event\LoggedOut;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class LogOutController implements RequestHandlerInterface
{
    public function __construct(
        protected Dispatcher $events,
        protected SessionAuthenticator $authenticator,
        protected Rememberer $rememberer,
        protected UrlGenerator $url,
        protected ReturnUrlValidator $returnUrl
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $session = $request->getAttribute('session');
        $actor = RequestUtil::getActor($request);
        $base = $this->url->to('forum')->base();

        $returnUrl = Arr::get($request->getQueryParams(), 'return');
        $return = $this->sanitizeReturnUrl((string) $returnUrl, $base);

        if ($actor->isGuest()) {
            return new RedirectResponse($return);
        }

        $accessToken = $session->get('access_token');
        $response = new RedirectResponse($return);

        $this->authenticator->logOut($session);

        $actor->accessTokens()->where('token', $accessToken)->delete();

        $this->events->dispatch(new LoggedOut($actor, false));

        return $this->rememberer->forget($response);
    }

    protected function sanitizeReturnUrl(string $url, string $base): Uri
    {
        return $this->returnUrl->sanitize($url, $base);
    }

    protected function getAllowedRedirectDomains(): array
    {
        return $this->returnUrl->allowedHosts();
    }
}
