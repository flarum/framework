<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Http\AccessToken;
use Flarum\Http\RequestUtil;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class DeleteAccessTokenController extends AbstractDeleteController
{
    protected function delete(Request $request): void
    {
        $actor = RequestUtil::getActor($request);
        $id = $request->query('id');

        $actor->assertRegistered();

        $token = AccessToken::query()->findOrFail($id);

        /** @var Session|null $session */
        $session = $request->attributes->get('session');

        // Current session should only be terminated through logout.
        if ($session && $token->token === $session->get('access_token')) {
            throw new PermissionDeniedException();
        }

        // Don't give away the existence of the token.
        if ($actor->cannot('revoke', $token)) {
            throw new ModelNotFoundException();
        }

        $token->delete();
    }
}
