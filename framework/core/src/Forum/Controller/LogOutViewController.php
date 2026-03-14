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
    public function __construct(
        protected Factory $view,
        protected UrlGenerator $url,
        protected Config $config
    ) {
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
            ->with('csrfToken', $session->token());

        return new HtmlResponse($view->render());
    }

    protected function sanitizeReturnUrl(string $url, string $base): Uri
    {
        if (empty($url)) {
            return new Uri($base);
        }

        try {
            $parsedUrl = new Uri($url);
        } catch (\InvalidArgumentException) {
            return new Uri($base);
        }

        if (in_array($parsedUrl->getHost(), $this->getAllowedRedirectDomains())) {
            return $parsedUrl;
        }

        return new Uri($base);
    }

    protected function getAllowedRedirectDomains(): array
    {
        $forumUri = $this->config->url();

        return array_merge(
            [$forumUri->getHost()],
            $this->config->offsetGet('redirectDomains') ?? []
        );
    }
}
