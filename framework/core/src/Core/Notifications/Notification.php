<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Notifications;

use Flarum\Core\Model;

/**
 * Models a notification record in the database.
 *
 * A notification record is associated with a user, and shows up in their
 * notification list. A notification indicates that something has happened that
 * the user should know about, like if a user's discussion was renamed by
 * someone else.
 *
 * Each notification record has a *type*. The type determines how the record
 * looks in the notifications list, and what *subject* is associated with it.
 * For example, the 'discussionRenamed' notification type represents that
 * someone renamed a user's discussion. Its subject is a discussion, of which
 * the ID is stored in the `subject_id` column.
 *
 * @todo document database columns with @property
 */
class Notification extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'notifications';

    /**
     * {@inheritdoc}
     */
    protected $dates = ['time'];

    /**
     * A map of notification types and the model classes to use for their
     * subjects. For example, the 'discussionRenamed' notification type, which
     * represents that a user's discussion was renamed, has the subject model
     * class 'Flarum\Core\Discussions\Discussion'.
     *
     * @var array
     */
    protected static $subjectModels = [];

    /**
     * Mark a notification as read.
     *
     * @return void
     */
    public function read()
    {
        $this->is_read = true;
    }

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
     * Get the subject model for this notification record by looking up its
     * type in our subject model map.
     *
     * @return string|null
     */
    public function getSubjectModelAttribute()
    {
        return array_get(static::$subjectModels, $this->type);
    }

    /**
     * Define the relationship with the notification's recipient.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('Flarum\Core\Users\User', 'user_id');
    }

    /**
     * Define the relationship with the notification's sender.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sender()
    {
        return $this->belongsTo('Flarum\Core\Users\User', 'sender_id');
    }

    /**
     * Define the relationship with the notification's subject.
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
     * Set the subject model for the given notification type.
     *
     * @param string $type The notification type.
     * @param string $subjectModel The class name of the subject model for that
     *     type.
     * @return void
     */
    public static function setSubjectModel($type, $subjectModel)
    {
        static::$subjectModels[$type] = $subjectModel;
    }
}
