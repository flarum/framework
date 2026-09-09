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
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string      $id
 * @property string      $access_token_id
 * @property bool        $revoked
 * @property Carbon      $expires_at
 * @property Carbon|null $created_at
 * @property AccessToken $accessToken
 */
class RefreshToken extends AbstractModel
{
    protected $table = 'oauth_provider_refresh_tokens';

    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $casts = [
        'revoked' => 'boolean',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected $guarded = [];

    public function accessToken(): BelongsTo
    {
        return $this->belongsTo(AccessToken::class, 'access_token_id');
    }
}
