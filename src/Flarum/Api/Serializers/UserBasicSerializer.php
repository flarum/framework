<?php namespace Flarum\Api\Serializers;

use Flarum\Core\Users\User;

class UserBasicSerializer extends BaseSerializer
{
    /**
     * The name to use for Flarum events.
     * @var string
     */
    protected static $eventName = 'UserBasic';

    /**
     * The resource type.
     * @var string
     */
    protected $type = 'users';

    /**
     * Serialize attributes of a User model for JSON output.
     * 
     * @param User $user The User model to serialize.
     * @return array
     */
    protected function attributes(User $user)
    {
        $attributes = [
            'id'        => (int) $user->id,
            'username'  => $user->username,
            'avatarUrl' => $user->avatar_url
        ];

        return $this->attributesEvent($user, $attributes);
    }

    /**
     * Get the URL templates where this resource and its related resources can
     * be accessed.
     * 
     * @return array
     */
    protected function href()
    {
        $href = [
            'users' => $this->action('UsersController@show', ['id' => '{users.id}'])
        ];

        return $this->hrefEvent($href);
    }
}
