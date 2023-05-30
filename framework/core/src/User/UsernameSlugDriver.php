<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Database\AbstractModel;
use Flarum\Http\SlugDriverInterface;

/**
 * @implements SlugDriverInterface<User>
 */
class UsernameSlugDriver implements SlugDriverInterface
{
    public function __construct(
        protected UserRepository $users
    ) {
    }

    /**
     * @param User $instance
     */
    public function toSlug(AbstractModel $instance): string
    {
        return $instance->username;
    }

    /**
     * @return User
     */
    public function fromSlug(string $slug, User $actor): AbstractModel
    {
        return $this->users->findOrFailByUsername($slug, $actor);
    }
}
