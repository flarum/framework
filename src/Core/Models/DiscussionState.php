<?php namespace Flarum\Core\Models;

use Flarum\Core\Events\DiscussionWasRead;
use Flarum\Core\Support\EventGenerator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Models a discussion-user state record in the database.
 *
 * Stores information about how much of a discussion a user has read. Can also
 * be used to store other information, if the appropriate columns are added to
 * the database, like a user's subscription status for a discussion.
 */
class DiscussionState extends Model
{
    use EventGenerator;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users_discussions';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected static $dateAttributes = ['read_time'];

    /**
     * Mark the discussion as being read up to a certain point. Raises the
     * DiscussionWasRead event.
     *
     * @param int $number
     * @return $this
     */
    public function read($number)
    {
        if ($number > $this->read_number) {
            $this->read_number = $number;
            $this->read_time   = time();

            $this->raise(new DiscussionWasRead($this));
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
        return $this->belongsTo('Flarum\Core\Models\Discussion', 'discussion_id');
    }

    /**
     * Define the relationship with the user that this state is for.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('Flarum\Core\Models\User', 'user_id');
    }

    /**
     * Set the keys for a save update query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        $query->where('discussion_id', $this->discussion_id)
              ->where('user_id', $this->user_id);

        return $query;
    }
}
