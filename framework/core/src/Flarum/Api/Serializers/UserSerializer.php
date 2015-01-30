<?php namespace Flarum\Api\Serializers;

use Flarum\Core\Users\User;

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

        $attributes += [
            'joinTime'         => $user->join_time ? $user->join_time->toRFC3339String() : null,
            'lastSeenTime'     => $user->last_seen_time ? $user->last_seen_time->toRFC3339String() : null,
            'discussionsCount' => (int) $user->discussions_count,
            'postsCount'       => (int) $user->posts_count,
            'canEdit'          => $user->permission('edit'),
            'canDelete'        => $user->permission('delete'),
        ];

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
