<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion;

use Flarum\Database\AbstractModel;
use Flarum\Http\SlugDriverInterface;
use Flarum\User\User;

/**
 * @implements SlugDriverInterface<Discussion>
 */
class Utf8SlugDriver implements SlugDriverInterface
{
    public function __construct(
        protected DiscussionRepository $discussions
    ) {
    }

    /**
     * @param Discussion $instance
     */
    public function toSlug(AbstractModel $instance): string
    {
        $slug = preg_replace('/[-\s]+/u', '-', $instance->title);
        $slug = preg_replace('/[^\p{L}\p{N}\p{M}_-]+/u', '', $slug);
        $slug = strtolower($slug);

        return $instance->id.(trim($slug) ? '-'.$slug : '');
    }

    /**
     * @return Discussion
     */
    public function fromSlug(string $slug, User $actor): AbstractModel
    {
        if (strpos($slug, '-')) {
            $slug_array = explode('-', $slug);
            $slug = $slug_array[0];
        }

        return $this->discussions->findOrFail($slug, $actor);
    }
}
