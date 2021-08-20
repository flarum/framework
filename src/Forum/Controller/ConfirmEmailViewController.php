<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Http\Controller\AbstractHtmlController;
use Flarum\Http\SessionAccessToken;
use Flarum\Http\SessionAuthenticator;
use Flarum\Http\UrlGenerator;
use Flarum\User\Command\ConfirmEmail;
use Flarum\User\EmailToken;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class ConfirmEmailViewController extends AbstractHtmlController
{
    /**
     * @var Factory
     */
    protected $view;

    /**
     * @param Factory $view
     */
    public function __construct(Factory $view)
    {
        $this->view = $view;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function render(Request $request)
    {
        $token = Arr::get($request->getQueryParams(), 'token');

        $token = EmailToken::validOrFail($token);

        return $this->view->make('flarum.forum::confirm-email')
            ->with('csrfToken', $request->getAttribute('session')->token());
    }
}
