<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Foundation\ValidationException;
use Flarum\Http\AccessToken;
use Flarum\Http\RequestUtil;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ServerRequestInterface;

class DeleteAccessTokenController extends AbstractDeleteController
{
    /**
     * {@inheritdoc}
     */
    protected function delete(ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);
        $id = Arr::get($request->getQueryParams(), 'id');

        $actor->assertRegistered();

        $token = AccessToken::query()->findOrFail($id);

        // Current session should only be terminated through logout.
        if ($token->token === $request->getAttribute('session')->token()) {
            throw new PermissionDeniedException();
        }

        // Don't give away the existance of the token.
        if ($actor->cannot('revoke', $token)) {
            throw new ModelNotFoundException();
        }

        $token->delete();

        return new EmptyResponse(204);
    }
}
