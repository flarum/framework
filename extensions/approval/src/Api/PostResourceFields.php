<?php

namespace Flarum\Approval\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Post\Post;

class PostResourceFields
{
    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('isApproved')
                ->writable(fn (Post $post, Context $context) => $context->getActor()->can('approve', $post)),
            Schema\Boolean::make('canApprove')
                ->get(fn (Post $post, Context $context) => $context->getActor()->can('approvePosts', $post->discussion)),
        ];
    }
}
