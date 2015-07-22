<?php namespace Flarum\Api\Serializers;

class UserSerializer extends UserBasicSerializer
{
    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($user)
    {
        $attributes = parent::getDefaultAttributes($user);

        $canEdit = $user->can($this->actor, 'edit');

        $attributes += [
            'bio'              => $user->bio,
            'joinTime'         => $user->join_time ? $user->join_time->toRFC3339String() : null,
            'discussionsCount' => (int) $user->discussions_count,
            'commentsCount'    => (int) $user->comments_count,
            'canEdit'          => $canEdit,
            'canDelete'        => $user->can($this->actor, 'delete'),
        ];

        if ($user->getPreference('discloseOnline')) {
            $attributes += [
                'lastSeenTime' => $user->last_seen_time ? $user->last_seen_time->toRFC3339String() : null
            ];
        }

        if ($canEdit) {
            $attributes += [
                'isActivated' => $user->is_activated,
                'email'       => $user->email,
                'isConfirmed' => $user->is_confirmed
            ];
        }

        return $attributes;
    }
}
