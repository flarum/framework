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
}
