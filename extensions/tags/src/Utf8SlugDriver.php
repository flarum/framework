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
        //
        // The lookup is keyed case-insensitively: Flarum's default collation
        // (utf8mb4_unicode_ci) makes the WHERE IN match differently-cased slugs,
        // so a query for "Extensions" can return the stored "extensions" row.
        // Keying on the raw stored slug would then miss the input entry and
        // raise "Undefined array key". Fold both sides to compare like-for-like.
        $foldedToInput = [];
        $decodedSlugs = [];
        foreach ($slugs as $slug) {
            $decoded = urldecode($slug);
            $decodedSlugs[] = $decoded;
            $foldedToInput[mb_strtolower($decoded)] = $slug;
        }

        /** @var Collection<string, Tag> $map */
        $map = new Collection();

        $tags = $this->repository
            ->query()
            ->whereIn('slug', $decodedSlugs)
            ->whereVisibleTo($actor)
            ->get();

        /** @var Tag $tag */
        foreach ($tags as $tag) {
            // Fall back to the tag's own slug if the folded input is somehow
            // absent, so a returned row is never dropped and never warns.
            $input = $foldedToInput[mb_strtolower($tag->slug)] ?? $tag->slug;
            $map[$input] = $tag;
        }

        return $map;
    }
}
