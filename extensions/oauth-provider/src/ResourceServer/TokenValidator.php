<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\ResourceServer;

use Carbon\Carbon;
use Flarum\OAuthProvider\Access\MemberAccess;
use Flarum\OAuthProvider\ClientRegistration\AllowedClients;
use Flarum\OAuthProvider\Models\AccessToken;
use Flarum\OAuthProvider\Server\AccessTokenVerifier;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;

/**
 * Validates OAuth access tokens presented to a protected resource registered
 * through Extend\Resource. Container singleton; holds no per-request state.
 */
class TokenValidator
{
    /**
     * last_used_at is written at most once per this many seconds per token.
     */
    public const int TOUCH_INTERVAL = 60;

    public function __construct(
        protected AccessTokenVerifier $verifier,
        protected AllowedClients $allowed,
        protected MemberAccess $access,
    ) {
    }

    /**
     * Returns null on any failure: bad signature, expired, revoked, issued
     * for another resource, or a user that no longer exists or may no longer
     * use applications (see MemberAccess).
     *
     * @param string|null $ipAddress Where the request came from, recorded as
     *                               where the token was last used.
     */
    public function validate(#[\SensitiveParameter] string $jwt, string $resource, ?string $ipAddress = null): ?ValidatedToken
    {
        try {
            return $this->doValidate($jwt, $resource, $ipAddress);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function doValidate(string $jwt, string $resource, ?string $ipAddress = null): ?ValidatedToken
    {
        $token = $this->verifier->verify($jwt, new PermittedFor($resource), new StrictValidAt($this->verifier->clock()));

        $tokenId = $token?->claims()->get('jti');

        if (! is_string($tokenId) || $tokenId === '') {
            return null;
        }

        /** @var AccessToken|null $row */
        $row = AccessToken::query()
            ->where('id', $tokenId)
            ->where('revoked', false)
            ->where('expires_at', '>', Carbon::now())
            ->whereHas('client', fn (Builder $query) => $this->allowed->scope($query)->where('revoked', false))
            ->first();

        // The row is authoritative: the JWT's aud must agree with what was granted.
        if ($row === null || $row->resource !== $resource) {
            return null;
        }

        /** @var User|null $user */
        $user = User::query()->find($row->user_id);

        if (! $this->access->allows($user)) {
            return null;
        }

        $this->touch($row, $ipAddress);

        return new ValidatedToken(
            user: $user,
            clientId: $row->client_id,
            scopes: array_values(array_map(strval(...), $row->scopes ?? [])),
            tokenId: $row->id,
            resource: $row->resource,
        );
    }

    protected function touch(AccessToken $row, ?string $ipAddress = null): void
    {
        $now = Carbon::now();

        if ($row->last_used_at !== null && $row->last_used_at->diffInSeconds($now, true) < self::TOUCH_INTERVAL) {
            return;
        }

        // A caller that doesn't say where it was called from leaves the last
        // known address in place.
        AccessToken::query()->where('id', $row->id)->update(array_filter([
            'last_used_at' => $now,
            'last_ip_address' => $ipAddress,
        ], fn ($value) => $value !== null));
    }
}
