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
 * @property string      $id
 * @property string      $name
 * @property string|null $secret
 * @property array       $redirect_uris
 * @property array|null  $scopes
 * @property bool        $confidential
 * @property bool        $revoked
 * @property int|null    $created_by
 * @property User|null   $creator
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Client extends AbstractModel
{
    protected $table = 'oauth_provider_clients';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'redirect_uris' => 'array',
        'scopes' => 'array',
        'confidential' => 'boolean',
        'revoked' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $guarded = [];

    protected $hidden = ['secret'];

    /**
     * Plain client secret — only populated at the moment of creation so the
     * API resource can return it to the admin once. Not persisted.
     */
    public ?string $plainSecret = null;

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hasRedirectUri(string $uri): bool
    {
        return in_array($uri, (array) $this->redirect_uris, true);
    }
}
