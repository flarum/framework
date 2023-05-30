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
use Tobscure\JsonApi\Relationship;

class BasicUserSerializer extends AbstractSerializer
{
    protected $type = 'users';

    public function __construct(
        protected SlugManager $slugManager
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function getDefaultAttributes(object|array $model): array
    {
        if (! ($model instanceof User)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.User::class
            );
        }

        return [
            'username'    => $model->username,
            'displayName' => $model->display_name,
            'avatarUrl'   => $model->avatar_url,
            'slug'        => $this->slugManager->forResource(User::class)->toSlug($model)
        ];
    }

    protected function groups($user): Relationship
    {
        if ($this->getActor()->can('viewHiddenGroups')) {
            return $this->hasMany($user, GroupSerializer::class);
        }

        return $this->hasMany($user, GroupSerializer::class, 'visibleGroups');
    }
}
