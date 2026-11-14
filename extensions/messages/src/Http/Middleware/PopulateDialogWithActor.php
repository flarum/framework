<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Http\Middleware;

use Flarum\Http\RequestUtil;
use Flarum\Messages\Dialog;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PopulateDialogWithActor implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Put back afterwards: a request made from inside this one (realtime's
        // per-recipient payloads, through the API client) must not leave its
        // actor behind, or the outer response resolves read state for the
        // wrong member.
        $previous = Dialog::stateUser();

        Dialog::setStateUser(RequestUtil::getActor($request));

        try {
            return $handler->handle($request);
        } finally {
            Dialog::setStateUser($previous);
        }
    }
}
