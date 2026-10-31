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
 * @property string      $source
 * @property string|null $logo_uri
 * @property string|null $client_uri
 * @property Carbon|null $metadata_expires_at
 * @property int|null    $created_by
 * @property User|null   $creator
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Client extends AbstractModel
{
    public const string SOURCE_ADMIN = 'admin';
    public const string SOURCE_METADATA_DOCUMENT = 'metadata_document';
    public const string SOURCE_DYNAMIC = 'dynamic';

    protected $table = 'oauth_provider_clients';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'redirect_uris' => 'array',
        'scopes' => 'array',
        'confidential' => 'boolean',
        'revoked' => 'boolean',
        'metadata_expires_at' => 'datetime',
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

    /**
     * The host a self-described client comes from: the client_id host for a
     * metadata document, the client_uri host otherwise. Admin clients have none.
     */
    public function sourceHost(): ?string
    {
        $url = match ($this->source) {
            self::SOURCE_METADATA_DOCUMENT => $this->id,
            self::SOURCE_DYNAMIC => $this->client_uri,
            default => null,
        };

        $host = $url === null ? null : parse_url($url, PHP_URL_HOST);

        return is_string($host) ? $host : null;
    }
}
