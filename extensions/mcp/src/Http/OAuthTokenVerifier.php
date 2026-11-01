<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Http;

use Flarum\OAuthProvider\ResourceServer\TokenValidator;
use Flarum\User\User;

/**
 * Checks an OAuth access token (JWT) issued by flarum/oauth-provider for the
 * MCP resource: signature, expiry, revocation and audience.
 */
class OAuthTokenVerifier
{
    public function __construct(
        protected TokenValidator $validator,
    ) {
    }

    /**
     * @return array{user: User, scopes: list<string>}|null
     */
    public function verify(#[\SensitiveParameter] string $jwt, string $resource): ?array
    {
        $token = $this->validator->validate($jwt, $resource);

        return $token ? ['user' => $token->user, 'scopes' => array_values($token->scopes)] : null;
    }
}
