<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Server\Entity;

use DateTimeImmutable;
use Lcobucci\JWT\Token;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

class AccessTokenEntity implements AccessTokenEntityInterface
{
    use AccessTokenTrait;
    use TokenEntityTrait;
    use EntityTrait;

    protected ?string $nonce = null;
    protected ?\DateTimeImmutable $authTime = null;
    protected ?string $resource = null;

    public function setResource(?string $resource): void
    {
        $this->resource = $resource;
    }

    public function getResource(): ?string
    {
        return $this->resource;
    }

    /**
     * Shadows AccessTokenTrait::convertToJWT() so `aud` is the RFC 8707
     * resource when the token is bound to one, else the client id.
     */
    private function convertToJWT(): Token
    {
        $this->initJwtConfiguration();

        return $this->jwtConfiguration->builder()
            ->permittedFor($this->resource ?? $this->getClient()->getIdentifier())
            ->identifiedBy($this->getIdentifier())
            ->issuedAt(new DateTimeImmutable())
            ->canOnlyBeUsedAfter(new DateTimeImmutable())
            ->expiresAt($this->getExpiryDateTime())
            ->relatedTo((string) $this->getUserIdentifier())
            ->withClaim('scopes', $this->getScopes())
            ->getToken($this->jwtConfiguration->signer(), $this->jwtConfiguration->signingKey());
    }

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
