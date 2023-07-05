<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Http\Controller\AbstractHtmlController;
use Flarum\User\EmailToken;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

class ConfirmEmailViewController extends AbstractHtmlController
{
    public function __construct(
        protected Factory $view
    ) {
    }

    public function render(Request $request): View
    {
        $token = Arr::get($request->getQueryParams(), 'token');

        EmailToken::validOrFail($token);

        return $this->view
            ->make('flarum.forum::confirm-email')
            ->with('csrfToken', $request->getAttribute('session')->token());
    }
}
