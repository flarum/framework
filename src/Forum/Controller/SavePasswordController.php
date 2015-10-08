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

use Flarum\Core\PasswordToken;
use Flarum\Core\Command\EditUser;
use Flarum\Forum\UrlGenerator;
use Flarum\Http\Controller\ControllerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\RedirectResponse;

class SavePasswordController implements ControllerInterface
{
    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @param UrlGenerator $url
     */
    public function __construct(UrlGenerator $url)
    {
        $this->url = $url;
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function handle(Request $request)
    {
        $input = $request->getParsedBody();

        $token = PasswordToken::findOrFail(array_get($input, 'token'));

        $password = array_get($input, 'password');
        $confirmation = array_get($input, 'password_confirmation');

        if (! $password || $password !== $confirmation) {
            return new RedirectResponse($this->url->toRoute('resetPassword', ['token' => $token->id]));
        }

        $token->user->changePassword($password);
        $token->user->save();

        $token->delete();

        return new RedirectResponse($this->url->toBase());
    }
}
