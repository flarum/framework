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

use Flarum\Core\Users\Commands\ConfirmEmail;
use Flarum\Api\Commands\GenerateAccessToken;
use Flarum\Core\Exceptions\InvalidConfirmationTokenException;
use Flarum\Support\Action;
use Illuminate\Contracts\Bus\Dispatcher;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\HtmlResponse;

class ConfirmEmailAction extends Action
{
    use WritesRememberCookie;

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
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(Request $request, array $routeParams = [])
    {
        try {
            $token = array_get($routeParams, 'token');

            $user = $this->bus->dispatch(
                new ConfirmEmail($token)
            );
        } catch (InvalidConfirmationTokenException $e) {
            return new HtmlResponse('Invalid confirmation token');
        }

        $token = $this->bus->dispatch(
            new GenerateAccessToken($user->id)
        );

        return $this->withRememberCookie(
            $this->redirectTo('/'),
            $token->id
        );
    }
}
