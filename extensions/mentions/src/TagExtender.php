<?php

namespace Flarum\Mentions;

use Flarum\Api\Controller;
use Flarum\Api\Serializer\BasicPostSerializer;
use Flarum\Extend;
use Flarum\Tags\Api\Serializer\TagSerializer;

class TagExtender
{
    public function __invoke(): array
    {
        return [
            (new Extend\Formatter)
                ->render(Formatter\FormatTagMentions::class)
                ->unparse(Formatter\UnparseTagMentions::class),

            (new Extend\ApiSerializer(BasicPostSerializer::class))
                ->hasMany('mentionsTags', TagSerializer::class),

            (new Extend\ApiController(Controller\ShowDiscussionController::class))
                ->load(['posts.mentionsTags']),

            (new Extend\ApiController(Controller\ListDiscussionsController::class))
                ->load([
                    'firstPost.mentionsTags', 'lastPost.mentionsTags',
                ]),

            (new Extend\ApiController(Controller\ListPostsController::class))
                ->load(['mentionsTags']),
        ];
    }
}
