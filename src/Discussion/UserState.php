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

/**
 * Models a discussion-user state record in the database.
 *
 * Stores information about how much of a discussion a user has read. Can also
 * be used to store other information, if the appropriate columns are added to
 * the database, like a user's subscription status for a discussion.
 *
 * @property int $user_id
 * @property int $discussion_id
 * @property \Carbon\Carbon|null $last_read_at
 * @property int|null $last_read_post_number
 * @property Discussion $discussion
 * @property \Flarum\User\User $user
 */
class UserState extends AbstractModel
{
    use EventGeneratorTrait;

    /**
     * {@inheritdoc}
     */
    protected $table = 'discussion_user';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['last_read_at'];

    /**
     * Mark the discussion as being read up to a certain point. Raises the
     * DiscussionWasRead event.
     *
     * @param int $number
     * @return $this
     */
    public function read($number)
    {
        if ($number > $this->last_read_post_number) {
            $this->last_read_post_number = $number;
            $this->last_read_at = Carbon::now();

            $this->raise(new UserRead($this));
        }

        return $this;
    }

    /**
     * Define the relationship with the discussion that this state is for.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function discussion()
    {
        return $this->belongsTo(Discussion::class);
    }

    /**
     * Define the relationship with the user that this state is for.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Set the keys for a save update query.
     *
     * @param Builder $query
     * @return Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        $query->where('discussion_id', $this->discussion_id)
              ->where('user_id', $this->user_id);

        return $query;
    }
}
