<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Api;

use Flarum\Ai\Ai;
use Flarum\Api\Context;
use Flarum\Api\Schema;

/**
 * `aiFeatures` on the forum: the keys of the features the member or guest
 * viewing it can use now, so a feature's frontend can decide whether to show
 * its controls.
 *
 * @internal
 */
class ForumResourceFields
{
    public function __construct(
        protected Ai $ai
    ) {
    }

    public function __invoke(): array
    {
        return [
            Schema\Arr::make('aiFeatures')
                ->get(fn (object $forum, Context $context) => $this->ai->availableTo($context->getActor())),
        ];
    }
}
