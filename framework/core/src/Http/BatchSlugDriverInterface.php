<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\User\User;
use Illuminate\Support\Collection;

/**
 * Optional companion to {@link SlugDriverInterface} for drivers that can resolve
 * many slugs in a single query. Consumers that resolve slugs in bulk (e.g. the
 * tag filter) should prefer this when the active driver implements it, and fall
 * back to looping {@link SlugDriverInterface::fromSlug()} otherwise.
 *
 * @template T of \Flarum\Database\AbstractModel
 */
interface BatchSlugDriverInterface
{
    /**
     * Resolve a set of slugs to their models, respecting actor visibility.
     * Slugs that do not resolve (unknown, or not visible to the actor) are
     * simply absent from the returned collection.
     *
     * @param string[] $slugs
     * @return Collection<string, T> models keyed by the slug they resolved from
     */
    public function fromSlugs(array $slugs, User $actor): Collection;
}
