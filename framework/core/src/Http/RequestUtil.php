<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\User\User;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface as Request;

class RequestUtil
{
    public static function isApiRequest(Request $request): bool
    {
        return Str::contains(
            $request->getHeaderLine('Accept'),
            'application/vnd.api+json'
        );
    }

    public static function isHtmlRequest(Request $request): bool
    {
        return Str::contains(
            $request->getHeaderLine('Accept'),
            'text/html'
        );
    }

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

        return $request;
    }
}
