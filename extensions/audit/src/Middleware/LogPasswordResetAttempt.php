<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Middleware;

use Flarum\Audit\AuditLogger;
use Flarum\User\UserRepository;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Logs password reset *requests* from the /api/forgot endpoint.
 *
 * Core processes the actual reset on a queued job (to avoid leaking whether an email exists),
 * so the worker-side token-creation hook can't see the originating request — the audit entry
 * it produces has no IP and a CLI client. This middleware captures the request itself, with the
 * real IP, and logs every attempt (including ones for unknown emails, which never reach the job)
 * so password-reset abuse and probing are visible.
 *
 * This is a distinct event from `user.password_change_requested` (logged by the worker when a
 * token is actually issued for a valid user): this is the *attempt*, that is the *fulfillment*.
 */
class LogPasswordResetAttempt implements MiddlewareInterface
{
    public function __construct(
        protected UserRepository $users
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getUri()->getPath() !== '/forgot') {
            return $handler->handle($request);
        }

        $email = Arr::get($request->getParsedBody(), 'email');

        $response = $handler->handle($request);

        // The controller returns an EmptyResponse on success (and only then); a validation
        // failure throws before reaching here, so we only log genuine, well-formed requests.
        if ($email && $response instanceof EmptyResponse) {
            $user = $this->users->findByEmail($email);

            if ($user) {
                // Known account: identify it by user_id and don't store the raw email.
                AuditLogger::log('user.password_reset_attempted', [
                    'matched' => true,
                    'user_id' => $user->id,
                ]);
            } else {
                // No matching account: keep the attempted email, which is the useful detail
                // for spotting reset abuse / enumeration.
                AuditLogger::log('user.password_reset_attempted', [
                    'matched' => false,
                    'email' => $email,
                ]);
            }
        }

        return $response;
    }
}
