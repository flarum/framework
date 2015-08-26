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
use Flarum\Core\Users\Commands\EditUser;
use Flarum\Support\Action;
use Illuminate\Contracts\Bus\Dispatcher;
use Psr\Http\Message\ServerRequestInterface as Request;

class SavePasswordAction extends Action
{
    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    /**
     * @param Request $request
     * @param array $routeParams
     * @return \Zend\Diactoros\Response\RedirectResponse
     */
    public function handle(Request $request, array $routeParams = [])
    {
        $input = $request->getParsedBody();

        $token = PasswordToken::findOrFail(array_get($input, 'token'));

        $password = array_get($input, 'password');
        $confirmation = array_get($input, 'password_confirmation');

        if (! $password || $password !== $confirmation) {
            return $this->redirectTo('/reset/'.$token->id); // TODO: Use UrlGenerator
        }

        $this->bus->dispatch(
            new EditUser($token->user_id, $token->user, ['attributes' => ['password' => $password]])
        );

        $token->delete();

        return $this->redirectTo('/');
    }
}
