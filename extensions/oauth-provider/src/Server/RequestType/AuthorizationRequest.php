<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Server\RequestType;

use League\OAuth2\Server\RequestTypes\AuthorizationRequest as LeagueAuthorizationRequest;

/**
 * Carries the RFC 8707 `resource` from authorization to auth code issuance.
 */
class AuthorizationRequest extends LeagueAuthorizationRequest
{
    protected ?string $resource = null;

    public static function fromLeague(LeagueAuthorizationRequest $request, ?string $resource): self
    {
        $copy = new self();
        $copy->setGrantTypeId($request->getGrantTypeId());
        $copy->setClient($request->getClient());
        $copy->setRedirectUri($request->getRedirectUri());
        $copy->setScopes($request->getScopes());

        if ($request->getState() !== null) {
            $copy->setState($request->getState());
        }

        if ($request->getCodeChallenge() !== null) {
            $copy->setCodeChallenge($request->getCodeChallenge());
            $copy->setCodeChallengeMethod($request->getCodeChallengeMethod());
        }

        $copy->resource = $resource;

        return $copy;
    }

    public function getResource(): ?string
    {
        return $this->resource;
    }
}
