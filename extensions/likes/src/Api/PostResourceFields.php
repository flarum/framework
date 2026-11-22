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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Arr;

class PostResourceFields
{
    public static int $maxLikes = 4;

    /**
     * The actor's own like count after an `isLiked` write.
     *
     * Returned as response `meta` rather than on the user resource so that the
     * client does not need a follow-up request, and other payloads keep their
     * usual shape. The field exists only on the update endpoint and only when
     * the request writes `isLiked`.
     */
    public static function actorLikeCount(): Schema\Integer
    {
        return Schema\Integer::make('actorLikeCount')
            ->description('When `isLiked` is written: the actor\'s stored like count after the change.')
            ->visible(fn (Post $post, Context $context) => Arr::has((array) $context->body(), 'data.attributes.isLiked'))
            ->get(fn (Post $post, Context $context) => (int) $post->getConnection()->table('users')
                ->where('id', $context->getActor()->id)->value('like_count'));
    }

    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('isLiked')
                ->description('Write-only: on an existing post, set to true to like it as you, false to unlike it.')
                ->visible(false)
                ->writable(fn (Post $post, Context $context) => $context->getActor()->can('like', $post))
                ->set(function (Post $post, bool $liked, Context $context) {
                    // The create endpoint can receive isLiked before the post exists.
                    if (! $post->exists) {
                        return;
                    }

                    $actor = $context->getActor();
                    $db = $post->getConnection();

                    $changed = $post->withDiscussionLock(function () use ($db, $post, $actor, $liked) {
                        // Match the discussion, post, then user lock order in deletion.
                        // The post's type and visibility decide whether its likes count.
                        $locked = $post->newModelQuery()->whereKey($post->id)->lockForUpdate()
                            ->firstOrFail(['id', 'type', 'is_private']);
                        $db->table('users')->where('id', $actor->id)->lockForUpdate()->get(['id']);

                        $currentlyLiked = $db->table('post_likes')->where('post_id', $post->id)
                            ->where('user_id', $actor->id)->lockForUpdate()->first(['user_id']) !== null;

                        $counted = $locked->type === 'comment' && ! $locked->is_private;

                        // Only add or remove this one like. A full recount of the
                        // actor's history stays for repair, not for every click.
                        if ($liked && ! $currentlyLiked) {
                            $post->likes()->attach($actor->id);

                            if ($counted) {
                                $db->table('users')->where('id', $actor->id)->update([
                                    'like_count' => new Expression('like_count + 1'),
                                ]);
                            }
                        } elseif (! $liked && $currentlyLiked) {
                            $post->likes()->detach($actor->id);

                            if ($counted) {
                                $db->table('users')->where('id', $actor->id)->update([
                                    'like_count' => new Expression('CASE WHEN like_count >= 1 THEN like_count - 1 ELSE 0 END'),
                                ]);
                            }
                        }

                        return $liked !== $currentlyLiked;
                    }, 3);

                    if ($changed) {
                        $post->raise($liked ? new PostWasLiked($post, $actor) : new PostWasUnliked($post, $actor));
                    }
                }),

            Schema\Boolean::make('canLike')
                ->get(fn (Post $post, Context $context) => $context->getActor()->can('like', $post)),
            Schema\Integer::make('likesCount')
                ->description('Number of users who liked the post.')
                ->countRelation('likes'),

            Schema\Relationship\ToMany::make('likes')
                ->description('Some of the users who liked the post, you first when you did.')
                ->type('users')
                ->includable()
                ->scope(function (BelongsToMany $query, Context $context) {
                    $actor = $context->getActor();
                    $grammar = $query->getQuery()->getGrammar();

                    // So that we can tell if the current user has liked the post.
                    $query
                        ->orderBy(new Expression($grammar->wrap('user_id').' = '.$actor->id), 'desc')
                        ->orderBy('created_at')
                        ->orderBy('user_id')
                        ->limit(static::$maxLikes)
                        // Serializing a liker reads their groups: load them for
                        // all the likers listed at once, not one by one.
                        ->with('groups');
                }),
        ];
    }
}
