<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Foundation\Config;
use Flarum\Http\Exception\TokenMismatchException;
use Flarum\Http\Rememberer;
use Flarum\Http\RequestUtil;
use Flarum\Http\SessionAuthenticator;
use Flarum\Http\UrlGenerator;
use Flarum\User\Event\LoggedOut;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class LogOutController implements RequestHandlerInterface
{
    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var SessionAuthenticator
     */
    protected $authenticator;

    /**
     * @var Rememberer
     */
    protected $rememberer;

    /**
     * @var Factory
     */
    protected $view;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Dispatcher $events
     * @param SessionAuthenticator $authenticator
     * @param Rememberer $rememberer
     * @param Factory $view
     * @param UrlGenerator $url
     * @param Config $config
     */
    public function __construct(
        Dispatcher $events,
        SessionAuthenticator $authenticator,
        Rememberer $rememberer,
        Factory $view,
        UrlGenerator $url,
        Config $config
    ) {
        $this->events = $events;
        $this->authenticator = $authenticator;
        $this->rememberer = $rememberer;
        $this->view = $view;
        $this->url = $url;
        $this->config = $config;
    }

    /**
     * @param Request $request
     * @return ResponseInterface
     * @throws TokenMismatchException
     */
    public function handle(Request $request): ResponseInterface
    {
        $session = $request->getAttribute('session');
        $actor = RequestUtil::getActor($request);
        $base = $this->url->to('forum')->base();

        $sanitizedUrl = $this->sanitizeReturnUrl((string) Arr::get($request->getQueryParams(), 'return', $base));

        // If there is no user logged in, return to the index.
        if ($actor->isGuest()) {
            return new RedirectResponse(empty($sanitizedUrl) ? $base : $sanitizedUrl);
        }

        // If a valid CSRF token hasn't been provided, show a view which will
        // allow the user to press a button to complete the log out process.
        $csrfToken = $session->token();

        if (Arr::get($request->getQueryParams(), 'token') !== $csrfToken) {
            $return = $this->sanitizeReturnUrl($request->getQueryParams()['return'] ?? $base);

            $view = $this->view->make('flarum.forum::log-out')
                ->with('url', $this->url->to('forum')->route('logout') . '?token=' . $csrfToken . ($return ? '&return=' . urlencode($return) : ''));

            return new HtmlResponse($view->render());
        }

        $accessToken = $session->get('access_token');
        $response = new RedirectResponse($sanitizedUrl);

        $this->authenticator->logOut($session);

        $actor->accessTokens()->where('token', $accessToken)->delete();

        $this->events->dispatch(new LoggedOut($actor, false));

        return $this->rememberer->forget($response);
    }

    protected function sanitizeReturnUrl(string $url): string
    {
        $parsed = parse_url($url);

        if (!$parsed || !isset($parsed['host'])) {
            return ''; // Return early for invalid URLs
        }

        $host = $parsed['host'];

        if (in_array($host, $this->getWhitelistedRedirectDomains())) {
            return $url;
        }

        return ''; // Return empty string for non-whitelisted domains
    }

    protected function getWhitelistedRedirectDomains(): array
    {
        return array_merge(
            [$this->config->url()],
            $this->config->offsetGet('redirectDomains') ?? []
        );
    }
}
