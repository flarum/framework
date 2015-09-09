<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Actions;

use Flarum\Api\Request;
use Flarum\Core\Users\UserRepository;
use Flarum\Core\Users\Commands\RequestPasswordReset;
use Illuminate\Contracts\Bus\Dispatcher;
use Zend\Diactoros\Response\EmptyResponse;

class ForgotAction implements Action
{
    protected $users;

    protected $bus;

    public function __construct(UserRepository $users, Dispatcher $bus)
    {
        $this->users = $users;
        $this->bus = $bus;
    }

    /**
     * Log in and return a token.
     *
     * @param \Flarum\Api\Request $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(Request $request)
    {
        $email = $request->get('email');

        $this->bus->dispatch(
            new RequestPasswordReset($email)
        );

        return new EmptyResponse();
    }
}
