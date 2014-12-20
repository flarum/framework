<?php namespace Flarum\Core\Discussions;

use Laracasts\Commander\Events\EventGenerator;

use Flarum\Core\Entity;

class DiscussionState extends Entity
{
    use EventGenerator;

    protected $table = 'users_discussions';

    public function getDates()
    {
        return ['read_time'];
    }

    public function discussion()
    {
        return $this->belongsTo('Flarum\Core\Discussions\Discussion', 'discussion_id');
    }

    public function user()
    {
        return $this->belongsTo('Flarum\Core\Users\User', 'user_id');
    }

    public function read($number)
    {
        $this->read_number = $number; // only if it's greater than the old one
        $this->read_time   = time();

        $this->raise(new Events\DiscussionWasRead($this));
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
