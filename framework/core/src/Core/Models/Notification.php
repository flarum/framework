<?php namespace Flarum\Core\Models;

use Flarum\Core\Support\MappedMorphTo;

class Notification extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notifications';

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['time'];

    /**
     * A map of notification types, as specified in the `type` column, to
     * their subject classes.
     *
     * @var array
     */
    protected static $types = [];

    public static function notify($userId, $type, $senderId, $subjectId, $data)
    {
        $notification = new static;

        $notification->user_id    = $userId;
        $notification->sender_id  = $senderId;
        $notification->type       = $type;
        $notification->subject_id = $subjectId;
        $notification->data       = $data;
        $notification->time       = time();

        return $notification;
    }

    public function read()
    {
        $this->is_read = true;
    }

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
     * Define the relationship with the notification's recipient.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('Flarum\Core\Models\User', 'user_id');
    }

    /**
     * Define the relationship with the notification's sender.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sender()
    {
        return $this->belongsTo('Flarum\Core\Models\User', 'sender_id');
    }

    public function subject()
    {
        $name = 'subject';
        $typeColumn = 'type';
        $idColumn = 'subject_id';

        // If the type value is null it is probably safe to assume we're eager loading
        // the relationship. When that is the case we will pass in a dummy query as
        // there are multiple types in the morph and we can't use single queries.
        if (is_null($type = $this->$typeColumn))
        {
            return new MappedMorphTo(
                $this->newQuery(), $this, $idColumn, null, $typeColumn, $name, static::$types
            );
        }

        // If we are not eager loading the relationship we will essentially treat this
        // as a belongs-to style relationship since morph-to extends that class and
        // we will pass in the appropriate values so that it behaves as expected.
        else
        {
            $class = static::$types[$type];
            $instance = new $class;

            return new MappedMorphTo(
                $instance->newQuery(), $this, $idColumn, $instance->getKeyName(), $typeColumn, $name, static::$types
            );
        }
    }

    public static function getTypes()
    {
        return static::$types;
    }

    /**
     * Register a notification type and its subject class.
     *
     * @param  string $type
     * @param  string $class
     * @return void
     */
    public static function addType($type, $class)
    {
        static::$types[$type] = $class;
    }
}
