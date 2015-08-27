<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Actions;

use Flarum\Core\Users\PasswordToken;
use Flarum\Support\HtmlAction;
use Flarum\Core\Exceptions\InvalidConfirmationTokenException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Contracts\View\Factory;
use DateTime;

class ResetPasswordAction extends HtmlAction
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
     * @param array $routeParams
     * @return \Illuminate\Contracts\View\View
     */
    public function render(Request $request, array $routeParams = [])
    {
        $token = array_get($routeParams, 'token');

        $token = PasswordToken::findOrFail($token);

        if ($token->created_at < new DateTime('-1 day')) {
            throw new InvalidConfirmationTokenException;
        }

        return $this->view->make('flarum::reset')->with('token', $token->id);
    }
}
