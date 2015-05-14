<?php namespace Flarum\Core\Models;

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
     *
     *
     * @var array
     */
    protected static $subjects = [];

    public static function alert($userId, $type, $senderId, $subjectId, $data)
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
        return $this->mappedMorphTo(static::$subjects, 'subject', 'type', 'subject_id');
    }

    public static function getTypes()
    {
        return static::$subjects;
    }

    /**
     * Register a notification type.
     *
     * @param  string $type
     * @param  string $class
     * @return void
     */
    public static function registerType($class)
    {
        if ($subject = $class::getSubjectModel()) {
            static::$subjects[$class::getType()] = $subject;
        }
    }
}
