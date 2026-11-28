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
use Flarum\User\User;
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
            // An attribute, not a relationship: it is sent on every update
            // whatever `include` the caller named, so a response can always say
            // which counts it changed. AffectedMentionUsers collects the users.
            // A count is only carried for a user the actor may read it from, as
            // on the user resource; the entry itself still names the user whose
            // list changed.
            Schema\Arr::make('affectedMentionUsers')
                ->description('Users whose mention count this update changed, as {id, mentionCount, mentionCountRevision}; the count and its revision are only present when you may read that user\'s count. Empty when no count changed.')
                ->visible(fn (Post $post, Context $context) => $context->updating())
                ->get(function (Post $post, Context $context) {
                    if (! $post->relationLoaded('affectedMentionUsers')) {
                        return [];
                    }

                    return array_values(array_map(function (User $user) use ($context) {
                        $entry = ['id' => (string) $user->id];

                        if ($context->getActor()->id === $user->id || $context->getActor()->can('viewMentionCount', $user)) {
                            $entry['mentionCount'] = (int) $user->mention_count;
                            $entry['mentionCountRevision'] = (int) $user->mention_count_revision;
                        }

                        return $entry;
                    }, $post->getRelation('affectedMentionUsers')->all()));
                }),
            Schema\Relationship\ToMany::make('mentionsGroups')
                ->type('groups'),
        ];
    }
}
