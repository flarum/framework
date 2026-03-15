<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\User\Exception\InvalidConfirmationTokenException;
use Flarum\User\RegistrationToken;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Resolves a registration token submitted in the POST body and returns the
 * non-sensitive fields needed to pre-populate the sign-up modal.
 *
 * The token is accepted in the body (not the URL) so it never appears in
 * server access logs, browser history, or Referer headers.
 *
 * Only username, email, and the list of pre-filled field names are returned.
 * Provider name, identifier, and payload internals are NOT exposed.
 *
 * No authentication is required — possession of the short-lived token is
 * proof of authorisation.
 */
class ResolveRegistrationTokenController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tokenValue = Arr::get($request->getParsedBody(), 'token');

        try {
            $token = RegistrationToken::validOrFail((string) $tokenValue);
        } catch (InvalidConfirmationTokenException) {
            return new JsonResponse(['errors' => [['status' => '404', 'title' => 'Not Found']]], 404);
        }

        $provided = array_keys($token->user_attributes ?? []);

        return new JsonResponse([
            'username' => $token->user_attributes['username'] ?? ($token->payload['suggested']['username'] ?? null),
            'email' => $token->user_attributes['email'] ?? ($token->payload['suggested']['email'] ?? null),
            'provided' => $provided,
        ]);
    }
}
