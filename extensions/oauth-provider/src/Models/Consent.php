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
 * @property bool        $revoked
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
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
