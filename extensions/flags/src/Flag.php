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
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $post_id
 * @property int $user_id
 * @property string $type
 * @property string $reason
 * @property string $reason_detail
 * @property Carbon $created_at
 *
 * @property-read Post $post
 * @property-read User $user
 */
class Flag extends AbstractModel
{
    use ScopeVisibilityTrait;

    protected $dates = ['created_at'];

    /**
     * @return BelongsTo<Post, self>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * @return BelongsTo<User, self>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
