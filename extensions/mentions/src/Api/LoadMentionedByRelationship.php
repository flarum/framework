<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Api;

use Flarum\Http\RequestUtil;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Apply visibility permissions to API data's mentionedBy relationship.
 * And limit mentionedBy to 3 posts only for performance reasons.
 */
class LoadMentionedByRelationship
{
    public const MAX_MENTIONED_BY = 4;

    public function __invoke(BelongsToMany $query, ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);

        return $query
            ->with(['mentionsPosts', 'mentionsPosts.user', 'mentionsUsers'])
            ->whereVisibleTo($actor)
            ->oldest()
            // Limiting a relationship results is only possible because
            // the Post model uses the \Staudenmeir\EloquentEagerLimit\HasEagerLimit
            // trait.
            ->limit(self::MAX_MENTIONED_BY);
    }
}
