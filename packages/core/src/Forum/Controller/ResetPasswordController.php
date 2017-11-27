<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use DateTime;
use Flarum\Http\Controller\AbstractHtmlController;
use Flarum\User\Exception\InvalidConfirmationTokenException;
use Flarum\User\PasswordToken;
use Illuminate\Contracts\View\Factory;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Contracts\Translation\Translator;

class ResetPasswordController extends AbstractHtmlController
{
    /**
     * @var Factory
     */
    protected $view;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @param Factory $view
     */
    public function __construct(Factory $view, Translator $translator)
    {
        $this->view = $view;
        $this->translator = $translator;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     * @throws \Flarum\User\Exception\InvalidConfirmationTokenException
     */
    public function render(Request $request)
    {
        $token = array_get($request->getQueryParams(), 'token');

        $token = PasswordToken::findOrFail($token);

        if ($token->created_at < new DateTime('-1 day')) {
            throw new InvalidConfirmationTokenException;
        }

        return $this->view->make('flarum::reset')
            ->with('translator', $this->translator)
            ->with('passwordToken', $token->id)
            ->with('csrfToken', $request->getAttribute('session')->get('csrf_token'))
            ->with('error', $request->getAttribute('session')->get('error'));
    }
}
