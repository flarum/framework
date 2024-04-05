<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Foundation\MaintenanceMode;
use Flarum\Http\Controller\AbstractHtmlController;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Maintenance login view.
 */
class LogInViewController extends AbstractHtmlController
{
    public function __construct(
        protected Factory $view,
        protected UrlGenerator $url,
        protected MaintenanceMode $maintenance
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);

        if (! $actor->isGuest() || ! $this->maintenance->inMaintenanceMode()) {
            return new RedirectResponse($this->url->to('forum')->base());
        }

        return parent::handle($request);
    }

    public function render(Request $request): View
    {
        return $this->view
            ->make('flarum.forum::log-in')
            ->with('csrfToken', $request->getAttribute('session')->token());
    }
}
