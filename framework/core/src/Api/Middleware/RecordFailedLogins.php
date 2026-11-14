<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Middleware;

use Flarum\User\Exception\NotAuthenticatedException;
use Flarum\User\Throttler\LoginThrottler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

/**
 * Counts a failed login for the LoginThrottler.
 *
 * A failure is counted here rather than in CreateTokenController, so it's
 * counted whichever controller handles the route: extensions such as
 * ianm/twofactor replace it with their own. Only wrong credentials count:
 * other rejections, like a missing CAPTCHA answer or a second factor still
 * to be given, come from someone who may know the password.
 */
class RecordFailedLogins implements Middleware
{
    public function __construct(
        protected LoginThrottler $throttler
    ) {
    }

    public function process(Request $request, Handler $handler): Response
    {
        if ($request->getAttribute('routeName') !== 'token' || $request->getAttribute('bypassThrottling')) {
            return $handler->handle($request);
        }

        try {
            return $handler->handle($request);
        } catch (NotAuthenticatedException $e) {
            $this->throttler->recordFailure($request);

            throw $e;
        }
    }
}
