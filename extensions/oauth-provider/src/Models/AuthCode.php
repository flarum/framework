<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Models;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string      $id
 * @property string      $client_id
 * @property int         $user_id
 * @property array|null  $scopes
 * @property string|null $nonce
 * @property Carbon|null $auth_time
 * @property string|null $resource
 * @property bool        $revoked
 * @property bool        $family_revoked
 * @property Carbon      $expires_at
 * @property Carbon|null $created_at
 * @property Client      $client
 * @property User        $user
 */
class AuthCode extends AbstractModel
{
    protected $table = 'oauth_provider_auth_codes';

    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $casts = [
        'scopes' => 'array',
        'revoked' => 'boolean',
        'family_revoked' => 'boolean',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'auth_time' => 'datetime',
    ];

    protected $guarded = [];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Revokes the codes, and the access and refresh tokens, that $scope
     * selects. It is applied to code and access token queries alike, so it may
     * only constrain columns both share (user_id, client_id).
     *
     * The codes' token families are marked first: a grant still issuing tokens
     * into one checks the mark once they are stored
     * (IssuesResourceBoundTokens::assertFamilyNotRevoked()), so tokens stored
     * after these updates are revoked as well.
     *
     * Rows already revoked are left alone, so this doesn't rewrite a whole
     * token history. Refresh tokens are still looked up through every access
     * token, revoked or not: one may outlive its access token.
     *
     * @param \Closure(Builder): Builder $scope
     */
    public static function revokeWithTokens(\Closure $scope): void
    {
        $scope(static::query())
            ->where(fn (Builder $query) => $query->where('revoked', false)->orWhere('family_revoked', false))
            ->update(['revoked' => true, 'family_revoked' => true]);

        RefreshToken::query()
            ->whereIn('access_token_id', $scope(AccessToken::query()->select('id')))
            ->where('revoked', false)
            ->update(['revoked' => true]);

        $scope(AccessToken::query())->where('revoked', false)->update(['revoked' => true]);
    }
}
