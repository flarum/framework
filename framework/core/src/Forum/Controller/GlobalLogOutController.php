<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Http\Rememberer;
use Flarum\Http\RequestUtil;
use Flarum\Http\SessionAuthenticator;
use Flarum\Http\UrlGenerator;
use Flarum\User\Event\LoggedOut;
use Illuminate\Contracts\Events\Dispatcher;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class GlobalLogOutController implements RequestHandlerInterface
{
    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var SessionAuthenticator
     */
    protected $authenticator;

    /**
     * @var Rememberer
     */
    protected $rememberer;

    /**
     * @var UrlGenerator
     */
    protected $url;

    public function __construct(
        Dispatcher $events,
        SessionAuthenticator $authenticator,
        Rememberer $rememberer,
        UrlGenerator $url
    ) {
        $this->events = $events;
        $this->authenticator = $authenticator;
        $this->rememberer = $rememberer;
        $this->url = $url;
    }

    public function handle(Request $request): ResponseInterface
    {
        $session = $request->getAttribute('session');
        $actor = RequestUtil::getActor($request);

        $actor->assertRegistered();

        $response = new RedirectResponse(
            $this->url->to('forum')->base()
        );

        $this->authenticator->logOut($session);

        $actor->accessTokens()->delete();
        $actor->emailTokens()->delete();
        $actor->passwordTokens()->delete();

        $this->events->dispatch(new LoggedOut($actor));

        return $this->rememberer->forget($response);
    }
}
