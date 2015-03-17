<?php namespace Flarum\Core\Models;

class Activity extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'activity';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['time'];

    /**
     * Unserialize the data attribute.
     *
     * @param  string  $value
     * @return string
     */
    public function getDataAttribute($value)
    {
        return json_decode($value);
    }

    /**
     * Serialize the data attribute.
     *
     * @param  string  $value
     */
    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value);
    }

    /**
     * Define the relationship with the activity's recipient.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('Flarum\Core\Models\User', 'user_id');
    }

    /**
     * Define the relationship with the activity's sender.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sender()
    {
        return $this->belongsTo('Flarum\Core\Models\User', 'sender_id');
    }

    /**
     * Define the relationship with the activity's sender.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function post()
    {
        return $this->belongsTo('Flarum\Core\Models\Post', 'post_id');
    }
}
