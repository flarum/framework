<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use DateTime;
use Flarum\Http\Controller\AbstractHtmlController;
use Flarum\User\Exception\InvalidConfirmationTokenException;
use Flarum\User\PasswordToken;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

class ResetPasswordController extends AbstractHtmlController
{
    public function __construct(
        protected Factory $view
    ) {
    }

    public function render(Request $request): View
    {
        $token = Arr::get($request->getQueryParams(), 'token');

        $token = PasswordToken::findOrFail($token);

        if ($token->created_at < new DateTime('-1 day')) {
            throw new InvalidConfirmationTokenException;
        }

        return $this->view
            ->make('flarum.forum::reset-password')
            ->with('passwordToken', $token->token)
            ->with('csrfToken', $request->getAttribute('session')->token());
    }
}
