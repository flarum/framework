<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Http\Controller\AbstractController;
use Flarum\Http\RememberAccessToken;
use Flarum\Http\SessionAccessToken;
use Flarum\User\Exception\NotAuthenticatedException;
use Flarum\User\UserRepository;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Not to be confused with the CreateAccessTokenController,
 * this controller is used to authenticate a user with credentials,
 * and return a system generated session-type access token.
 */
class CreateTokenController extends AbstractController
{
    public function __construct(
        protected UserRepository $users,
        protected BusDispatcher $bus,
        protected EventDispatcher $events
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $identification = $request->json('identification');
        $password = $request->json('password');

        $user = $identification
            ? $this->users->findByIdentification($identification)
            : null;

        if (! $user || ! $user->checkPassword($password)) {
            throw new NotAuthenticatedException;
        }

        if ($request->json('remember')) {
            $token = RememberAccessToken::generate($user->id);
        } else {
            $token = SessionAccessToken::generate($user->id);
        }

        // We do a first update here to log the IP/agent of the token creator, even if the token is never used afterwards
        $token->touch(request: $request);

        return new JsonResponse([
            'token' => $token->token,
            'userId' => $user->id
        ]);
    }
}
