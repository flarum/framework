<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Server\ResponseType;

use Flarum\OAuthProvider\Server\IdTokenBuilder;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse;

/**
 * Extends the standard bearer token response to include an `id_token` in the
 * token endpoint response when the `openid` scope was granted. Required by
 * OpenID Connect Core 1.0 §3.1.3.3.
 */
class IdTokenResponse extends BearerTokenResponse
{
    public function __construct(protected IdTokenBuilder $builder)
    {
    }

    protected function getExtraParams(AccessTokenEntityInterface $accessToken): array
    {
        $idToken = $this->builder->build($accessToken);

        if ($idToken === null) {
            return [];
        }

        return ['id_token' => $idToken];
    }
}
