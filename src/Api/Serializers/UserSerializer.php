<?php namespace Flarum\Api\Serializers;

use Flarum\Core\Models\User;

class UserSerializer extends UserBasicSerializer
{
    /**
     * The name to use for Flarum events.
     * @var string
     */
    protected static $eventName = 'User';

    /**
     * Default relations to include.
     * @var array
     */
    protected $include = ['groups'];

    /**
     * Serialize attributes of a User model for JSON output.
     *
     * @param User $user The User model to serialize.
     * @return array
     */
    protected function attributes(User $user)
    {
        $attributes = parent::attributes($user);

        $actorUser = static::$actor->getUser();
        $canEdit = $user->can($actorUser, 'edit');

        $attributes += [
            'bioHtml'          => $user->bioHtml,
            'joinTime'         => $user->join_time ? $user->join_time->toRFC3339String() : null,
            'lastSeenTime'     => $user->last_seen_time ? $user->last_seen_time->toRFC3339String() : null,
            'discussionsCount' => (int) $user->discussions_count,
            'commentsCount'    => (int) $user->comments_count,
            'canEdit'          => $canEdit,
            'canDelete'        => $user->can($actorUser, 'delete'),
        ];

        if ($canEdit) {
            $attributes += [
                'bio'         => $user->bio,
                'isActivated' => $user->is_activated,
                'email'       => $user->email,
                'isConfirmed' => $user->is_confirmed
            ];
        }

        if ($user->id === $actorUser->id) {
            $attributes += [
                'readTime' => $user->read_time ? $user->read_time->toRFC3339String() : null,
            ];
        }

        return $this->attributesEvent($user, $attributes);
    }

    /**
     * Get a collection containing a user's groups.
     *
     * @param User $user
     * @param array $relations
     * @return Tobscure\JsonApi\Collection
     */
    protected function includeGroups(User $user, $relations)
    {
        return (new GroupSerializer($relations))->collection($user->groups);
    }
}
