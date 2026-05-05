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
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string      $id
 * @property string      $client_id
 * @property int         $user_id
 * @property array|null  $scopes
 * @property string|null $nonce
 * @property Carbon|null $auth_time
 * @property bool        $revoked
 * @property Carbon      $expires_at
 * @property Carbon|null $created_at
 * @property Client      $client
 * @property User        $user
 * @property RefreshToken|null $refreshToken
 */
class AccessToken extends AbstractModel
{
    protected $table = 'oauth_provider_access_tokens';

    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $casts = [
        'scopes' => 'array',
        'revoked' => 'boolean',
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

    public function refreshToken(): HasOne
    {
        return $this->hasOne(RefreshToken::class, 'access_token_id');
    }
}
