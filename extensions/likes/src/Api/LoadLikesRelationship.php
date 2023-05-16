<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes\Api;

use Flarum\Discussion\Discussion;
use Flarum\Http\RequestUtil;
use Flarum\Post\Post;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Expression;
use Psr\Http\Message\ServerRequestInterface;

class LoadLikesRelationship
{
    public static $maxLikes = 4;

    public static function mutateRelation(BelongsToMany $query, ServerRequestInterface $request): BelongsToMany
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

    /**
     * Called using the @see ApiController::prepareDataForSerialization extender.
     */
    public static function countRelation($controller, $data): void
    {
        $loadable = null;

        if ($data instanceof Discussion) {
            $loadable = $data->newCollection($data->posts)->filter(function ($post) {
                return $post instanceof Post;
            });
        } elseif ($data instanceof Collection) {
            $loadable = $data;
        } elseif ($data instanceof Post) {
            $loadable = $data->newCollection([$data]);
        }

        if ($loadable) {
            $loadable->loadCount('likes');
        }
    }
}
