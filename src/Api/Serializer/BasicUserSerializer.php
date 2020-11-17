<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use Flarum\Http\SlugManager;
use Flarum\User\User;
use InvalidArgumentException;

class BasicUserSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'users';

    /**
     * @var SlugManager
     */
    protected $slugManager;

    public function __construct(SlugManager $slugManager)
    {
        $this->slugManager = $slugManager;
    }

    /**
     * {@inheritdoc}
     *
     * @param User $user
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes($user)
    {
        if (! ($user instanceof User)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.User::class
            );
        }

        return [
            'username'    => $user->username,
            'displayName' => $user->display_name,
            'avatarUrl'   => $user->avatar_url,
            'slug'        => $this->slugManager->forResource(User::class)->toSlug($user)
        ];
    }

    /**
     * @return \Tobscure\JsonApi\Relationship
     */
    protected function groups($user)
    {
        if ($this->getActor()->can('viewHiddenGroups')) {
            return $this->hasMany($user, GroupSerializer::class);
        }

        return $this->hasMany($user, GroupSerializer::class, 'visibleGroups');
    }
}
