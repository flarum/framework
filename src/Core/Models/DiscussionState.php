<?php namespace Flarum\Core\Models;

use Flarum\Core\Events\DiscussionWasRead;

class DiscussionState extends Model
{
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
    protected $dates = ['read_time'];

    /**
     * Mark the discussion as read to a certain point by updating that state's
     * data.
     *
     * @param  int  $number
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
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery(\Illuminate\Database\Eloquent\Builder $query)
    {
        $query->where('discussion_id', $this->discussion_id)
              ->where('user_id', $this->user_id);

        return $query;
    }
}
