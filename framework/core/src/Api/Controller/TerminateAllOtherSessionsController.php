<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Http\RememberAccessToken;
use Flarum\Http\RequestUtil;
use Flarum\Http\SessionAccessToken;
use Illuminate\Database\Eloquent\Builder;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ServerRequestInterface;

class TerminateAllOtherSessionsController extends AbstractDeleteController
{
    /**
     * {@inheritdoc}
     */
    protected function delete(ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);

        $actor->assertRegistered();

        $session = $request->getAttribute('session');
        $sessionAccessToken = $session ? $session->get('access_token') : null;

        // Delete all session access tokens except for this one.
        $actor
            ->accessTokens()
            ->where('token', '!=', $sessionAccessToken)
            ->where(function (Builder $query) {
                $query
                    ->where('type', SessionAccessToken::$type)
                    ->orWhere('type', RememberAccessToken::$type);
            })->delete();

        return new EmptyResponse(204);
    }
}
