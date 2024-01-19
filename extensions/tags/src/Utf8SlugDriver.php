<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags;

use Flarum\Database\AbstractModel;
use Flarum\Http\SlugDriverInterface;
use Flarum\User\User;

/**
 * @implements SlugDriverInterface<Tag>
 */
class Utf8SlugDriver implements SlugDriverInterface
{
    public function __construct(
        protected TagRepository $repository
    ) {
    }

    /**
     * @param Tag $instance
     */
    public function toSlug(AbstractModel $instance): string
    {
        return $instance->slug;
    }

    /**
     * @return Tag
     */
    public function fromSlug(string $slug, User $actor): AbstractModel
    {
        /** @var Tag $tag */
        $tag = $this->repository
            ->query()
            ->where('slug', urldecode($slug))
            ->whereVisibleTo($actor)
            ->firstOrFail();

        return $tag;
    }
}
