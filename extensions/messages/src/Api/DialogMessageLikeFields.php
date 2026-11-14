<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Messages\DialogMessage;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Expression;

/**
 * What a message says about its likes, with flarum/likes enabled.
 */
class DialogMessageLikeFields
{
    /** As many likers as a message lists; the rest are counted. */
    public const MAX_LIKES = 4;

    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('canLike')
                ->get(fn (DialogMessage $message, Context $context) => $context->getActor()->can('like', $message)),
            Schema\Integer::make('likesCount')
                ->description('Number of members who liked the message.')
                ->countRelation('likes'),
            Schema\Relationship\ToMany::make('likes')
                ->description('Some of the members who liked the message, you first when you did.')
                ->type('users')
                ->includable()
                ->scope(function (BelongsToMany $query, Context $context) {
                    $grammar = $query->getQuery()->getGrammar();

                    $query
                        ->orderBy(new Expression($grammar->wrap('user_id').' = '.(int) $context->getActor()->id), 'desc')
                        ->orderBy('created_at')
                        ->orderBy('user_id')
                        ->limit(self::MAX_LIKES)
                        ->with('groups');
                }),
        ];
    }
}
