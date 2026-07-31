<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Access;

use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tags\Tag;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;

class GlobalPolicy extends AbstractPolicy
{
    /**
     * Verdicts per "{actor id}:{ability}", so repeated checks within a
     * request (e.g. canStartDiscussion serialized per tag) don't re-count.
     * An instance property rather than a function static: policies live for
     * one container, so this can never leak across requests — or tests.
     *
     * @var array<string, bool>
     */
    protected array $enoughTags = [];

    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function can(User $actor, string $ability): ?string
    {
        if ($ability === 'startDiscussion'
            && $actor->hasPermission($ability)
            && $actor->hasPermission('bypassTagCounts')) {
            return $this->allow();
        }

        if (! in_array($ability, ['viewForum', 'startDiscussion'])) {
            return null;
        }

        $minPrimary = (int) $this->settings->get('flarum-tags.min_primary_tags');
        $minSecondary = (int) $this->settings->get('flarum-tags.min_secondary_tags');

        if ($ability === 'startDiscussion' && $minPrimary === 0 && $minSecondary === 0) {
            return null;
        }

        $this->enoughTags["$actor->id:$ability"] ??= $this->enoughTagsWithPermission($actor, $ability, $minPrimary, $minSecondary);

        return $this->enoughTags["$actor->id:$ability"] ? $this->allow() : $this->deny();
    }

    /**
     * Can the actor see at least the configured minimum of primary and
     * secondary tags?
     *
     * A pair whose minimum is 0 is trivially satisfied and costs nothing. The
     * rest is answered by ONE aggregate scan counting both permission-scoped
     * totals at once — this used to be four separate COUNT queries on every
     * request. For viewForum, a forum with fewer tags than the minimum only
     * requires that all of them are visible; those grand totals are fetched
     * lazily, only when a permission count falls short of its minimum.
     */
    protected function enoughTagsWithPermission(User $actor, string $ability, int $minPrimary, int $minSecondary): bool
    {
        if ($minPrimary === 0 && $minSecondary === 0) {
            return true;
        }

        // Unqualified column names: raw fragments bypass the builder's
        // identifier prefixing, so `tags.position` would break on installs
        // with a table prefix. The permission subquery lives in the WHERE
        // clause under an alias, so nothing here is ambiguous.
        $withPermission = Tag::whereHasPermission($actor, $ability)
            ->toBase()
            ->selectRaw('coalesce(sum(case when position is not null then 1 else 0 end), 0) as primary_count')
            ->selectRaw('coalesce(sum(case when position is null then 1 else 0 end), 0) as secondary_count')
            ->first();

        $enoughPrimary = $minPrimary === 0 || $withPermission->primary_count >= $minPrimary;
        $enoughSecondary = $minSecondary === 0 || $withPermission->secondary_count >= $minSecondary;

        if ($ability === 'viewForum' && (! $enoughPrimary || ! $enoughSecondary)) {
            $totals = Tag::query()
                ->toBase()
                ->selectRaw('coalesce(sum(case when position is not null then 1 else 0 end), 0) as primary_count')
                ->selectRaw('coalesce(sum(case when position is null and parent_id is null then 1 else 0 end), 0) as secondary_count')
                ->first();

            $enoughPrimary = $enoughPrimary || $withPermission->primary_count >= min($totals->primary_count, $minPrimary);
            $enoughSecondary = $enoughSecondary || $withPermission->secondary_count >= min($totals->secondary_count, $minSecondary);
        }

        return $enoughPrimary && $enoughSecondary;
    }
}
