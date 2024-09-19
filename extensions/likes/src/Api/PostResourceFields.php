<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Likes\Event\PostWasLiked;
use Flarum\Likes\Event\PostWasUnliked;
use Flarum\Post\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;

class PostResourceFields
{
    public static int $maxLikes = 4;

    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('isLiked')
                ->visible(false)
                ->writable(fn (Post $post, Context $context) => $context->getActor()->can('like', $post))
                ->set(function (Post $post, bool $liked, Context $context) {
                    $actor = $context->getActor();

                    $currentlyLiked = $post->likes()->where('user_id', $actor->id)->exists();

                    if ($liked && ! $currentlyLiked) {
                        $post->likes()->attach($actor->id);

                        $post->raise(new PostWasLiked($post, $actor));
                    } elseif ($currentlyLiked) {
                        $post->likes()->detach($actor->id);

                        $post->raise(new PostWasUnliked($post, $actor));
                    }
                }),

            Schema\Boolean::make('canLike')
                ->get(fn (Post $post, Context $context) => $context->getActor()->can('like', $post)),
            Schema\Integer::make('likesCount')
                ->countRelation('likes'),

            Schema\Relationship\ToMany::make('likes')
                ->type('users')
                ->includable()
                ->scope(function (Builder $query, Context $context) {
                    $actor = $context->getActor();
                    $grammar = $query->getQuery()->getGrammar();

                    // So that we can tell if the current user has liked the post.
                    $query
                        ->orderBy(new Expression($grammar->wrap('user_id').' = '.$actor->id), 'desc')
                        ->orderBy('created_at')
                        ->limit(static::$maxLikes);
                }),
        ];
    }
}
