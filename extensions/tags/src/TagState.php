<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags;

use Flarum\Database\AbstractModel;
use Flarum\Foundation\EventGeneratorTrait;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $user_id
 * @property int $tag_id
 * @property \Carbon\Carbon|null $marked_as_read_at
 * @property bool $is_hidden
 * @property Tag $tag
 * @property User $user
 */
class TagState extends AbstractModel
{
    use EventGeneratorTrait;

    protected $table = 'tag_user';

    protected $casts = ['marked_as_read_at' => 'datetime'];

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Set the keys for a save update query.
     *
     * @param Builder $query
     * @return Builder
     */
    protected function setKeysForSaveQuery($query)
    {
        $query->where('tag_id', $this->tag_id)
              ->where('user_id', $this->user_id);

        return $query;
    }
}
