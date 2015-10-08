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

use Flarum\Core\Command\ConfirmEmail;
use Flarum\Api\Command\GenerateAccessToken;
use Flarum\Core\Exception\InvalidConfirmationTokenException;
use Flarum\Foundation\Application;
use Flarum\Http\Controller\ControllerInterface;
use Illuminate\Contracts\Bus\Dispatcher;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\RedirectResponse;

class ConfirmEmailController implements ControllerInterface
{
    use WriteRememberCookieTrait;

    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @param Dispatcher $bus
     * @param Application $app
     */
    public function __construct(Dispatcher $bus, Application $app)
    {
        $this->bus = $bus;
        $this->app = $app;
    }

    /**
     * @param Request $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function handle(Request $request)
    {
        try {
            $token = array_get($request->getQueryParams(), 'token');

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
            new RedirectResponse($this->app->url()),
            $token->id
        );
    }
}
