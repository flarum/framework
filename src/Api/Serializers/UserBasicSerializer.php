<?php namespace Flarum\Api\Serializers;

class UserBasicSerializer extends Serializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'users';

    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($user)
    {
        return [
            'username'  => $user->username,
            'avatarUrl' => $user->avatar_url
        ];
    }

    /**
     * @return callable
     */
    protected function groups()
    {
        return $this->hasMany('Flarum\Api\Serializers\GroupSerializer');
    }
}
