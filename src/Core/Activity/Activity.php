<?php namespace Flarum\Core\Activity;

use Flarum\Core\Model;

/**
 * Models a user activity record in the database.
 *
 * Activity records show up in chronological order on a user's activity feed.
 * They indicate when the user has done something, like making a new post. They
 * can also be used to show any other relevant information on the user's
 * activity feed, like if the user has been mentioned in another post.
 *
 * Each activity record has a *type*. The type determines how the record looks
 * in the activity feed, and what *subject* is associated with it. For example,
 * the 'posted' activity type represents that a user made a post. Its subject is
 * a post, of which the ID is stored in the `subject_id` column.
 *
 * @todo document database columns with @property
 */
class Activity extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'activity';

    /**
     * {@inheritdoc}
     */
    protected static $dateAttributes = ['time'];

    /**
     * A map of activity types and the model classes to use for their subjects.
     * For example, the 'posted' activity type, which represents that a user
     * made a post, has the subject model class 'Flarum\Core\Posts\Post'.
     *
     * @var array
     */
    protected static $subjectModels = [];

    /**
     * When getting the data attribute, unserialize the JSON stored in the
     * database into a plain array.
     *
     * @param string $value
     * @return mixed
     */
    public function getDataAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * When setting the data attribute, serialize it into JSON for storage in
     * the database.
     *
     * @param mixed $value
     */
    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value);
    }

    /**
     * Get the subject model for this activity record by looking up its type in
     * our subject model map.
     *
     * @return string|null
     */
    public function getSubjectModelAttribute()
    {
        return array_get(static::$subjectModels, $this->type);
    }

    /**
     * Define the relationship with the activity's recipient.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('Flarum\Core\Users\User', 'user_id');
    }

    /**
     * Define the relationship with the activity's subject.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function subject()
    {
        return $this->morphTo('subject', 'subjectModel', 'subject_id');
    }

    /**
     * Get the type-to-subject-model map.
     *
     * @return array
     */
    public static function getSubjectModels()
    {
        return static::$subjectModels;
    }

    /**
     * Set the subject model for the given activity type.
     *
     * @param string $type The activity type.
     * @param string $subjectModel The class name of the subject model for that
     *     type.
     * @return void
     */
    public static function setSubjectModel($type, $subjectModel)
    {
        static::$subjectModels[$type] = $subjectModel;
    }
}
