<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\ResourceServer;

use Flarum\User\User;

readonly class ValidatedToken
{
    /**
     * @param list<string> $scopes
     */
    public function __construct(
        public User $user,
        public string $clientId,
        public array $scopes,
        public string $tokenId,
        public ?string $resource,
    ) {
    }
}
