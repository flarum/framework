<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\Database\ScopeVisibilityTrait;
use Flarum\Post\Post;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int|null $post_id
 * @property int|null $target_user_id
 * @property int $user_id
 * @property string $type
 * @property string|null $reason
 * @property string|null $reason_detail
 * @property Carbon $created_at
 *
 * @property-read Post|null $post
 * @property-read User|null $targetUser
 * @property-read User $user
 */
class Flag extends AbstractModel
{
    use ScopeVisibilityTrait;
    use HasFactory;

    public $timestamps = true;

    public const UPDATED_AT = null;

    protected $casts = ['created_at' => 'datetime'];

    protected $fillable = ['post_id', 'target_user_id', 'type', 'user_id'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
