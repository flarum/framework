<?php

namespace Flarum\Approval\Api;

use Flarum\Api\Schema;

class DiscussionResourceFields
{
    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('isApproved'),
        ];
    }
}
