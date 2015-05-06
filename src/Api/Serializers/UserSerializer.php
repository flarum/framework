<?php namespace Flarum\Api\Serializers;

class UserSerializer extends UserBasicSerializer
{
    /**
     * Default relations to include.
     *
     * @var array
     */
    protected $include = ['groups'];

    /**
     * Serialize attributes of a User model for JSON output.
     *
     * @param User $user The User model to serialize.
     * @return array
     */
    protected function attributes($user)
    {
        $attributes = parent::attributes($user);

        $actorUser = $this->actor->getUser();
        $canEdit = $user->can($actorUser, 'edit');

        $attributes += [
            'bioHtml'          => $user->bioHtml,
            'joinTime'         => $user->join_time ? $user->join_time->toRFC3339String() : null,
            'discussionsCount' => (int) $user->discussions_count,
            'commentsCount'    => (int) $user->comments_count,
            'canEdit'          => $canEdit,
            'canDelete'        => $user->can($actorUser, 'delete'),
        ];

        if ($user->preference('discloseOnline')) {
            $attributes += [
                'lastSeenTime' => $user->last_seen_time ? $user->last_seen_time->toRFC3339String() : null
            ];
        }

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
                'unreadNotificationsCount' => $user->getUnreadNotificationsCount(),
                'preferences' => $user->preferences
            ];
        }

        return $this->extendAttributes($user, $attributes);
    }
}
