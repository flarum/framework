<?php namespace Flarum\Api\Serializers;

class CurrentUserSerializer extends UserSerializer
{
    protected function attributes($user)
    {
        $attributes = parent::attributes($user);

        $actingUser = $this->actor->getUser();

        if ($user->id === $actingUser->id) {
            $attributes += [
                'readTime' => $user->read_time ? $user->read_time->toRFC3339String() : null,
                'unreadNotificationsCount' => $user->getUnreadNotificationsCount(),
                'preferences' => $user->preferences
            ];
        }

        return $this->extendAttributes($user, $attributes);
    }
}
