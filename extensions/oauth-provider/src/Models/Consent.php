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
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int         $id
 * @property int         $user_id
 * @property string      $client_id
 * @property array|null  $scopes
 * @property string|null $redirect_uris_hash
 * @property bool        $revoked
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $last_used_at Selected by ConsentResource, not a column.
 * @property string|null $last_ip_address Selected by ConsentResource, not a column.
 * @property User        $user
 * @property Client      $client
 */
class Consent extends AbstractModel
{
    protected $table = 'oauth_provider_user_consents';

    protected $casts = [
        'scopes' => 'array',
        'revoked' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Withdraws this approval and everything the member granted the client
     * with it: their codes, access tokens and refresh tokens. The client must
     * ask for consent again.
     */
    public function revokeAccess(): void
    {
        static::revoke($this->user_id, $this->client_id);
    }

    /**
     * Disconnects every application a member connected. Returns whether they
     * had connected any.
     */
    public static function revokeAllFor(int $userId): bool
    {
        return static::revoke($userId);
    }

    /**
     * Returns whether a consent was deleted.
     */
    protected static function revoke(int $userId, ?string $clientId = null): bool
    {
        $forClient = fn ($query) => $clientId === null ? $query : $query->where('client_id', $clientId);

        // Codes and tokens are revoked on both sides of deleting the consent,
        // which closes the races with requests still running:
        // - An authorization re-checks the consent once its code is stored
        //   (AuthorizeController::completeAuthorization()): it either sees the
        //   consent gone and withdraws, or stored its code before the delete,
        //   and the second pass revokes it, and any token issued from it.
        // - Each pass catches a grant still issuing tokens
        //   (AuthCode::revokeWithTokens()).
        // Revoking before the delete too means a disconnect that fails halfway
        // leaves the connection listed, so the member can disconnect it again.
        $grants = fn ($query) => $forClient($query->where('user_id', $userId));

        AuthCode::revokeWithTokens($grants);
        $deleted = $forClient(static::query()->where('user_id', $userId))->delete();
        AuthCode::revokeWithTokens($grants);

        return $deleted > 0;
    }

    /**
     * Identifies a set of redirect URIs regardless of order: consent given to
     * a client only stands while it sends users back to the same places.
     *
     * @param array<string> $redirectUris
     */
    public static function hashRedirectUris(array $redirectUris): string
    {
        $redirectUris = array_values(array_unique($redirectUris));
        sort($redirectUris);

        return hash('sha256', (string) json_encode($redirectUris));
    }

    /**
     * Whether consent was given while the client had these redirect URIs.
     *
     * @param array<string> $redirectUris
     */
    public function coversRedirectUris(array $redirectUris): bool
    {
        return $this->redirect_uris_hash !== null && hash_equals($this->redirect_uris_hash, self::hashRedirectUris($redirectUris));
    }

    /**
     * Does this consent record cover every one of the given scope identifiers?
     */
    public function covers(array $requestedScopes): bool
    {
        $granted = $this->scopes ?? [];

        foreach ($requestedScopes as $scope) {
            if (! in_array($scope, $granted, true)) {
                return false;
            }
        }

        return true;
    }
}
