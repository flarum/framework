<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Server\Entity;

use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\Traits\AuthCodeTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

class AuthCodeEntity implements AuthCodeEntityInterface
{
    use AuthCodeTrait;
    use TokenEntityTrait;
    use EntityTrait;

    protected ?string $nonce = null;
    protected ?\DateTimeImmutable $authTime = null;

    public function setNonce(?string $nonce): void
    {
        $this->nonce = $nonce;
    }

    public function getNonce(): ?string
    {
        return $this->nonce;
    }

    public function setAuthTime(?\DateTimeImmutable $authTime): void
    {
        $this->authTime = $authTime;
    }

    public function getAuthTime(): ?\DateTimeImmutable
    {
        return $this->authTime;
    }
}
