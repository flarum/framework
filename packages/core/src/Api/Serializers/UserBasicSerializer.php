<?php namespace Flarum\Api\Serializers;

class UserBasicSerializer extends BaseSerializer
{
    /**
     * The resource type.
     *
     * @var string
     */
    protected $type = 'users';

    /**
     * Serialize attributes of a User model for JSON output.
     *
     * @param User $user The User model to serialize.
     * @return array
     */
    protected function attributes($user)
    {
        $attributes = [
            'username'  => $user->username,
            'avatarUrl' => $user->avatar_url
        ];

        return $this->extendAttributes($user, $attributes);
    }

    protected function groups()
    {
        return $this->hasMany('Flarum\Api\Serializers\GroupSerializer');
    }
}
