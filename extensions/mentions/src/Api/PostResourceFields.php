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
                ->countRelation('mentionedBy', function (Builder $query, Context $context) {
                    $query->whereVisibleTo($context->getActor());
                }),

            Schema\Relationship\ToMany::make('mentionedBy')
                ->type('posts')
                ->includable()
                // Serializing these posts checks whether the actor can see each
                // one, and that check reads the post's discussion; rendering
                // their content reads what they mention. Their author is
                // included with them, but only after their fields are, so the
                // checks among those that read it would each load it. Without
                // the eager loads every mentioning post fetches these separately.
                ->scope(fn (BelongsToMany $query, Context $context) => $query
                    ->with([
                        'discussion',
                        'user' => fn (Relation $user) => $user->whereVisibleTo($context->getActor()),
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
                ->type('users'),
            Schema\Relationship\ToMany::make('mentionsGroups')
                ->type('groups'),
        ];
    }
}
