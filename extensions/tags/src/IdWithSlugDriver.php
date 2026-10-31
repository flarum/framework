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
 * Produces tag slugs in the form `<id>-<slug>` (e.g. `1-general`), resolving by
 * the leading id so the trailing text is cosmetic. Mirrors the discussion
 * IdWithTransliteratedSlugDriver.
 *
 * @implements SlugDriverInterface<Tag>
 * @implements BatchSlugDriverInterface<Tag>
 */
class IdWithSlugDriver implements SlugDriverInterface, BatchSlugDriverInterface
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
        return $instance->id.(trim((string) $instance->slug) ? '-'.$instance->slug : '');
    }

    /**
     * @return Tag
     */
    public function fromSlug(string $slug, User $actor): AbstractModel
    {
        return $this->repository->findOrFail($this->id($slug), $actor);
    }

    public function fromSlugs(array $slugs, User $actor): Collection
    {
        /** @var Collection<string, Tag> $map */
        $map = new Collection();

        // Map each leading id back to the original input slug it came from.
        $idToInput = [];
        foreach ($slugs as $slug) {
            $idToInput[$this->id($slug)] = $slug;
        }

        $tags = $this->repository
            ->queryVisibleTo($actor)
            ->whereIn('id', array_keys($idToInput))
            ->get();

        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $map[$idToInput[(int) $tag->id]] = $tag;
        }

        return $map;
    }

    /**
     * Extract the leading id from an `<id>-<slug>` value. The text after the
     * first hyphen is cosmetic and ignored.
     */
    private function id(string $slug): int
    {
        return (int) (strpos($slug, '-') !== false ? explode('-', $slug, 2)[0] : $slug);
    }
}
