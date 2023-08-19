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
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
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
 * @property array|null $data
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $read_at
 * @property \Carbon\Carbon $deleted_at
 * @property-read \Flarum\User\User|null $user
 * @property-read \Flarum\User\User|null $fromUser
 * @property-read \Flarum\Database\AbstractModel|\Flarum\Post\Post|\Flarum\Discussion\Discussion|null $subject
 * @method static \Illuminate\Database\Eloquent\Builder<Notification> matchingBlueprint(BlueprintInterface $blueprint)
 */
class Notification extends AbstractModel
{
    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'from_user_id' => 'integer',
        'subject_id' => 'integer',
        'data' => 'array',
        'created_at' => 'datetime',
        'read_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * A map of notification types and the model classes to use for their
     * subjects. For example, the 'discussionRenamed' notification type, which
     * represents that a user's discussion was renamed, has the subject model
     * class 'Flarum\Discussion\Discussion'.
     *
     * @var array<string, class-string<AbstractModel>>
     */
    protected static array $subjectModels = [];

    public function read(): void
    {
        $this->read_at = Carbon::now();
    }

    /**
     * Get the subject model for this notification record by looking up its
     * type in our subject model map.
     */
    public function getSubjectModelAttribute(): ?string
    {
        return $this->type ? Arr::get(static::$subjectModels, $this->type) : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * @return MorphTo
     */
    public function subject(): MorphTo
    {
        return $this->morphTo('subject', 'subjectModel');
    }

    /**
     * Scope the query to include only notifications whose subjects are visible
     * to the given user.
     */
    public function scopeWhereSubjectVisibleTo(Builder $query, User $actor): Builder
    {
        return $query->where(function ($query) use ($actor) {
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

                            if (method_exists($class, 'registerVisibilityScoper')) {
                                $class::query()->setQuery($query)->whereVisibleTo($actor);
                            }
                        });
                });
            }
        });
    }

    /**
     * Scope the query to include only notifications that have the given
     * subject.
     */
    public function scopeWhereSubject(Builder $query, AbstractModel $model): Builder
    {
        return $query->whereSubjectModel(get_class($model))
            ->where('subject_id', $model->getAttribute('id'));
    }

    /**
     * Scope the query to include only notification types that use the given
     * subject model.
     */
    public function scopeWhereSubjectModel(Builder $query, string $class): Builder
    {
        $notificationTypes = array_filter(self::getSubjectModels(), function (string $modelClass) use ($class) {
            return $modelClass === $class || is_subclass_of($class, $modelClass);
        });

        return $query->whereIn('type', array_keys($notificationTypes));
    }

    /**
     * Scope the query to find all records matching the given blueprint.
     */
    public function scopeMatchingBlueprint(Builder $query, BlueprintInterface $blueprint): Builder
    {
        return $query->where(static::getBlueprintAttributes($blueprint));
    }

    /**
     * Send notifications to the given recipients.
     *
     * @param User[] $recipients
     */
    public static function notify(array $recipients, BlueprintInterface $blueprint): void
    {
        $attributes = static::getBlueprintAttributes($blueprint);
        $now = Carbon::now()->toDateTimeString();

        static::insert(
            array_map(function (User $user) use ($attributes, $now) {
                return $attributes + [
                    'user_id' => $user->id,
                    'created_at' => $now
                ];
            }, $recipients)
        );
    }

    /**
     * Get the type-to-subject-model map.
     */
    public static function getSubjectModels(): array
    {
        return static::$subjectModels;
    }

    /**
     * Set the subject model for the given notification type.
     *
     * @param string $type The notification type.
     * @param class-string<AbstractModel> $subjectModel The class name of the subject model for that
     *     type.
     */
    public static function setSubjectModel(string $type, string $subjectModel): void
    {
        static::$subjectModels[$type] = $subjectModel;
    }

    protected static function getBlueprintAttributes(BlueprintInterface $blueprint): array
    {
        return [
            'type' => $blueprint::getType(),
            'from_user_id' => ($fromUser = $blueprint->getFromUser()) ? $fromUser->id : null,
            'subject_id' => ($subject = $blueprint->getSubject()) ? $subject->getAttribute('id') : null,
            'data' => ($data = $blueprint->getData()) ? json_encode($data) : null
        ];
    }
}
