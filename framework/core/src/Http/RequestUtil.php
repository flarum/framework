<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\User\User;
use Psr\Http\Message\ServerRequestInterface as Request;

class RequestUtil
{
    public static function getActor(Request $request): User
    {
        return $request->getAttribute('actorReference')->getActor();
    }

    public static function withActor(Request $request, User $actor): Request
    {
        $actorReference = $request->getAttribute('actorReference');

        if (! $actorReference) {
            $actorReference = new ActorReference;
            $request = $request->withAttribute('actorReference', $actorReference);
        }

        $actorReference->setActor($actor);

        // @deprecated in 1.0
        $request = $request->withAttribute('actor', $actor);

        return $request;
    }
}
