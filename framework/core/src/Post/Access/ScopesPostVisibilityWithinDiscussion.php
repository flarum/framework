<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Access;

use Flarum\Discussion\Discussion;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * A post visibility scoper that can take a fast path when every post in the
 * query belongs to one discussion the actor is already allowed to see.
 *
 * The generic `view` scope must embed discussion-level access checks into the
 * SQL as per-row subqueries, because the posts may span discussions. When the
 * discussion is known and pre-authorized, those checks are constants: they can
 * be evaluated once in PHP and the subqueries omitted, which matters on large
 * discussions where the generic scope's cost grows with the post count.
 *
 * Implementations MUST apply the same post-level restrictions as their
 * ordinary scoper; only discussion-level conditions may be lifted out.
 * Scopers that don't implement this interface are simply applied as usual.
 */
interface ScopesPostVisibilityWithinDiscussion
{
    public function withinDiscussion(User $actor, Builder $query, Discussion $discussion): void;
}
