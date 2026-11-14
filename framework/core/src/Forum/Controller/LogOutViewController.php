<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Foundation\Config;
use Flarum\Http\RequestUtil;
use Flarum\Http\ReturnUrlValidator;
use Flarum\Http\UrlGenerator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class LogOutViewController implements RequestHandlerInterface
{
    protected ReturnUrlValidator $returnUrl;

    public function __construct(
        protected Factory $view,
        protected UrlGenerator $url,
        protected Config $config,
        ?ReturnUrlValidator $returnUrl = null
    ) {
        $this->returnUrl = $returnUrl ?? resolve(ReturnUrlValidator::class);
    }

    public function handle(Request $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $base = $this->url->to('forum')->base();

        $returnUrl = Arr::get($request->getQueryParams(), 'return');
        $return = $this->sanitizeReturnUrl((string) $returnUrl, $base);

        if ($actor->isGuest()) {
            return new RedirectResponse($return);
        }

        $session = $request->getAttribute('session');

        $postUrl = $this->url->to('forum')->route('logout').($returnUrl ? '?return='.urlencode($return) : '');

        $view = $this->view->make('flarum.forum::log-out')
            ->with('url', $postUrl)
            ->with('csrfToken', $session->token())
            ->with('actorClass', RequestUtil::getActorHtmlClass($request));

        return new HtmlResponse($view->render());
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
