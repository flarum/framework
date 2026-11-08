<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Server;

use DateTimeImmutable;
use Flarum\Http\UrlGenerator;
use Flarum\OAuthProvider\KeyManager;
use Flarum\OAuthProvider\Models\AccessToken as AccessTokenModel;
use Flarum\User\UserRepository;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;

/**
 * Builds OpenID Connect ID tokens per OIDC Core 1.0 §2.
 *
 * Issued when the `openid` scope was granted. Signed with the same RSA private
 * key used for access tokens (RS256), so the same JWKS entry verifies both.
 */
class IdTokenBuilder
{
    public function __construct(
        protected KeyManager $keys,
        protected UrlGenerator $url,
        protected UserRepository $users,
    ) {
    }

    /**
     * @return string|null The serialized JWT, or null if no `openid` scope was granted.
     */
    public function build(AccessTokenEntityInterface $accessToken): ?string
    {
        $scopes = array_map(fn ($s) => $s->getIdentifier(), $accessToken->getScopes());

        if (! in_array('openid', $scopes, true)) {
            return null;
        }

        /** @var AccessTokenModel|null $persisted */
        $persisted = AccessTokenModel::query()->find($accessToken->getIdentifier());

        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->keys->privateKey()->getKeyContents()),
            InMemory::plainText($this->keys->publicKey()->getKeyContents()),
        );

        $now = new DateTimeImmutable();
        $expiry = $accessToken->getExpiryDateTime();

        $builder = $config->builder()
            ->issuedBy($this->issuer())
            ->relatedTo((string) $accessToken->getUserIdentifier())
            ->permittedFor($accessToken->getClient()->getIdentifier())
            ->issuedAt($now)
            ->expiresAt($expiry instanceof DateTimeImmutable ? $expiry : DateTimeImmutable::createFromMutable($expiry));

        if ($persisted?->nonce) {
            $builder = $builder->withClaim('nonce', $persisted->nonce);
        }

        if ($persisted?->auth_time) {
            $builder = $builder->withClaim('auth_time', $persisted->auth_time->getTimestamp());
        }

        // Profile / email claims are mirrored into the ID token when those
        // scopes are granted, per OIDC Core 1.0 §5.4.
        if ((int) $accessToken->getUserIdentifier() > 0) {
            $user = $this->users->findOrFail((int) $accessToken->getUserIdentifier());

            if (in_array('profile', $scopes, true)) {
                $builder = $builder
                    ->withClaim('name', $user->display_name)
                    ->withClaim('picture', $user->avatar_url);
            }

            if (in_array('email', $scopes, true)) {
                $builder = $builder
                    ->withClaim('email', $user->email)
                    ->withClaim('email_verified', (bool) $user->is_email_confirmed);
            }
        }

        return $builder->getToken($config->signer(), $config->signingKey())->toString();
    }

    public function issuer(): string
    {
        return rtrim($this->url->to('forum')->base(), '/');
    }
}
