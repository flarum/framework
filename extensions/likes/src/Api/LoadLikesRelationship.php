<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes\Api;

use Flarum\Http\RequestUtil;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Expression;
use Psr\Http\Message\ServerRequestInterface;

class LoadLikesRelationship
{
    public static $maxLikes = 4;

    public function __invoke(BelongsToMany $query, ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);

        $grammar = $query->getQuery()->getGrammar();

        return $query
            // So that we can tell if the current user has liked the post.
            ->orderBy(new Expression($grammar->wrap('user_id').' = '.$actor->id), 'desc')
            // Limiting a relationship results is only possible because
            // the Post model uses the \Staudenmeir\EloquentEagerLimit\HasEagerLimit
            // trait.
            ->limit(self::$maxLikes);
    }
}
