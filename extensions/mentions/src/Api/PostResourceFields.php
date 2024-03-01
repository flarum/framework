<?php

namespace Flarum\Mentions\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Post\Post;

class PostResourceFields
{
    public static int $maxMentionedBy = 4;

    public function __invoke(): array
    {
        return [
            Schema\Integer::make('mentionedByCount')
                ->countRelation('mentionedBy'),

            Schema\Relationship\ToMany::make('mentionedBy')
                ->type('posts')
                ->includable()
                ->limit(static::$maxMentionedBy),
            Schema\Relationship\ToMany::make('mentionsPosts')
                ->type('posts'),
            Schema\Relationship\ToMany::make('mentionsUsers')
                ->type('users'),
            Schema\Relationship\ToMany::make('mentionsGroups')
                ->type('groups'),
        ];
    }
}
