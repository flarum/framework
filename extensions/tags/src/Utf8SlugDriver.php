<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags;

use Flarum\Database\AbstractModel;
use Flarum\Http\BatchSlugDriverInterface;
use Flarum\Http\SlugDriverInterface;
use Flarum\User\User;
use Illuminate\Support\Collection;

/**
 * @implements SlugDriverInterface<Tag>
 * @implements BatchSlugDriverInterface<Tag>
 */
class Utf8SlugDriver implements SlugDriverInterface, BatchSlugDriverInterface
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

    public function fromSlugs(array $slugs, User $actor): Collection
    {
        // Map decoded slug (the stored column value) back to the caller's input
        // slug, so the returned collection is keyed exactly as it was queried —
        // matching fromSlug()'s urldecode() while staying transparent to callers.
        $decodedToInput = [];
        foreach ($slugs as $slug) {
            $decodedToInput[urldecode($slug)] = $slug;
        }

        /** @var Collection<string, Tag> $map */
        $map = new Collection();

        $tags = $this->repository
            ->query()
            ->whereIn('slug', array_keys($decodedToInput))
            ->whereVisibleTo($actor)
            ->get();

        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $map[$decodedToInput[$tag->slug]] = $tag;
        }

        return $map;
    }
}
