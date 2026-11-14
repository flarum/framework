<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Server\Entity;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class ClientEntity implements ClientEntityInterface
{
    use EntityTrait;
    use ClientTrait;

    /**
     * The scopes the client may be granted; null when unrestricted.
     *
     * @var list<string>|null
     */
    protected ?array $registeredScopes = null;

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setRedirectUri(array|string $uri): void
    {
        $this->redirectUri = $uri;
    }

    public function setConfidential(bool $confidential): void
    {
        $this->isConfidential = $confidential;
    }

    /**
     * @param list<string>|null $scopes
     */
    public function setRegisteredScopes(?array $scopes): void
    {
        $this->registeredScopes = $scopes;
    }

    /**
     * @return list<string>|null
     */
    public function getRegisteredScopes(): ?array
    {
        return $this->registeredScopes;
    }
}
