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
use Illuminate\Support\Str;

/**
 * @implements SlugDriverInterface<User>
 */
class IdWithDisplayNameSlugDriver implements SlugDriverInterface
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
        return $instance->id.'-'.Str::slug($instance->display_name);
    }

    /**
     * @return User
     */
    public function fromSlug(string $slug, User $actor): AbstractModel
    {
        if (strpos($slug, '-') !== false) {
            $slug_array = explode('-', $slug);
            $slug = $slug_array[0];
        }

        return $this->users->findOrFail($slug, $actor);
    }
}
