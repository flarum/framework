<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\Discussion\Event\UserRead;
use Flarum\Foundation\EventGeneratorTrait;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Models a discussion-user state record in the database.
 *
 * Stores information about how much of a discussion a user has read. Can also
 * be used to store other information, if the appropriate columns are added to
 * the database, like a user's subscription status for a discussion.
 *
 * @property int $user_id
 * @property int $discussion_id
 * @property Carbon|null $last_read_at
 * @property int|null $last_read_post_number
 * @property-read Discussion $discussion
 * @property-read User $user
 */
class UserState extends AbstractModel
{
    use EventGeneratorTrait;

    protected $table = 'discussion_user';

    protected $casts = [
        'user_id' => 'integer',
        'discussion_id' => 'integer',
        'last_read_post_number' => 'integer',
        'last_read_at' => 'datetime'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = ['last_read_post_number'];

    /**
     * Mark the discussion as being read up to a certain point. Raises the
     * DiscussionWasRead event.
     */
    public function read(int $number): static
    {
        if ($number > $this->last_read_post_number) {
            $this->last_read_post_number = $number;
            $this->last_read_at = Carbon::now();

            $this->raise(new UserRead($this));
        }

        return $this;
    }

    public function discussion(): BelongsTo
    {
        return $this->belongsTo(Discussion::class);
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
        $query
            ->where('discussion_id', $this->discussion_id)
            ->where('user_id', $this->user_id);

        return $query;
    }
}
