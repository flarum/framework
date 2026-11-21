<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Post\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;

class PostResourceFields
{
    public static int $maxMentionedBy = 4;

    public function __invoke(): array
    {
        return [
            Schema\Integer::make('mentionedByCount')
                ->description('Number of posts you can see that reply to (mention) this post.')
                ->countRelation('mentionedBy', function (Builder $query, Context $context) {
                    $query->whereVisibleTo($context->getActor());
                }),

            Schema\Relationship\ToMany::make('mentionedBy')
                ->description('Some of the posts that reply to (mention) this post; mentionedByCount has the total.')
                ->type('posts')
                ->includable()
                // Serializing these posts checks whether the actor can see each
                // one, and that check reads the post's discussion; rendering
                // their content reads what they mention. Their author is
                // included with them, but only after their fields are, so the
                // checks among those that read it would each load it, and
                // serializing the author reads their groups. Without the eager
                // loads every mentioning post fetches these separately.
                ->scope(fn (BelongsToMany $query, Context $context) => $query
                    ->with([
                        'discussion',
                        'user' => fn (Relation $user) => $user->whereVisibleTo($context->getActor()),
                        'user.groups',
                        // Their content links to the posts they mention, so only
                        // those the actor can see may be loaded.
                        'mentionsPosts' => fn (Relation $mentioned) => $mentioned->whereVisibleTo($context->getActor()),
                        'mentionsPosts.user',
                        'mentionsPosts.discussion',
                        'mentionsUsers',
                        'mentionsGroups',
                    ])
                    ->oldest('id')
                    ->limit(static::$maxMentionedBy)),
            Schema\Relationship\ToMany::make('mentionsPosts')
                ->type('posts'),
            Schema\Relationship\ToMany::make('mentionsUsers')
                ->type('users')
                ->includable()
                // The composer uses the updated users to refresh its store.
                ->visible(fn (Post $post, Context $context) => $context->creating()),
            Schema\Relationship\ToMany::make('affectedMentionUsers')
                ->description('Users whose mention was added or removed by this edit.')
                ->type('users')
                ->includable()
                ->visible(fn (Post $post, Context $context) => $context->updating())
                ->get(fn (Post $post) => $post->relationLoaded('affectedMentionUsers') ? $post->getRelation('affectedMentionUsers')->all() : []),
            Schema\Relationship\ToMany::make('mentionsGroups')
                ->type('groups'),
        ];
    }
}
