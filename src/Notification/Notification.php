<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\Event\ScopeModelVisibility;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

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
 * @property int $id
 * @property int $user_id
 * @property int|null $from_user_id
 * @property string $type
 * @property int|null $subject_id
 * @property mixed|null $data
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $read_at
 * @property \Carbon\Carbon $deleted_at
 * @property \Flarum\User\User|null $user
 * @property \Flarum\User\User|null $fromUser
 * @property \Flarum\Database\AbstractModel|null $subject
 */
class Notification extends AbstractModel
{
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'read_at'];

    /**
     * A map of notification types and the model classes to use for their
     * subjects. For example, the 'discussionRenamed' notification type, which
     * represents that a user's discussion was renamed, has the subject model
     * class 'Flarum\Discussion\Discussion'.
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
        $this->read_at = Carbon::now();
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
        return $this->type ? Arr::get(static::$subjectModels, $this->type) : null;
    }

    /**
     * Define the relationship with the notification's recipient.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Define the relationship with the notification's sender.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * Define the relationship with the notification's subject.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function subject()
    {
        return $this->morphTo('subject', 'subjectModel');
    }

    /**
     * Scope the query to include only notifications whose subjects are visible
     * to the given user.
     *
     * @param Builder $query
     */
    public function scopeWhereSubjectVisibleTo(Builder $query, User $actor)
    {
        $query->where(function ($query) use ($actor) {
            $classes = [];

            foreach (static::$subjectModels as $type => $class) {
                $classes[$class][] = $type;
            }

            foreach ($classes as $class => $types) {
                $query->orWhere(function ($query) use ($types, $class, $actor) {
                    $query->whereIn('type', $types)
                        ->whereExists(function ($query) use ($class, $actor) {
                            $query->selectRaw(1)
                                ->from((new $class)->getTable())
                                ->whereColumn('id', 'subject_id');

                            static::$dispatcher->dispatch(
                                new ScopeModelVisibility($class::query()->setQuery($query), $actor, 'view')
                            );
                        });
                });
            }
        });
    }

    /**
     * Scope the query to include only notifications that have the given
     * subject.
     *
     * @param Builder $query
     * @param object $model
     */
    public function scopeWhereSubject(Builder $query, $model)
    {
        $query->whereSubjectModel(get_class($model))
            ->where('subject_id', $model->id);
    }

    /**
     * Scope the query to include only notification types that use the given
     * subject model.
     *
     * @param Builder $query
     * @param string $class
     */
    public function scopeWhereSubjectModel(Builder $query, string $class)
    {
        $notificationTypes = array_filter(self::getSubjectModels(), function ($modelClass) use ($class) {
            return $modelClass === $class or is_subclass_of($class, $modelClass);
        });

        $query->whereIn('type', array_keys($notificationTypes));
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
