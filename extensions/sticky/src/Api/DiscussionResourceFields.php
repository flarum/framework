<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Sticky\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Discussion\Discussion;
use Flarum\Sticky\Event\DiscussionWasStickied;
use Flarum\Sticky\Event\DiscussionWasUnstickied;

class DiscussionResourceFields
{
    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('isSticky')
                ->writable(function (Discussion $discussion, Context $context) {
                    return $context->updating()
                        && $context->getActor()->can('sticky', $discussion);
                })
                ->set(function (Discussion $discussion, bool $isSticky, Context $context) {
                    $actor = $context->getActor();

                    if ($discussion->is_sticky === $isSticky) {
                        return;
                    }

                    $discussion->is_sticky = $isSticky;

                    $discussion->raise(
                        $discussion->is_sticky
                            ? new DiscussionWasStickied($discussion, $actor)
                            : new DiscussionWasUnstickied($discussion, $actor)
                    );
                }),
            Schema\Boolean::make('canSticky')
                ->get(fn (Discussion $discussion, Context $context) => $context->getActor()->can('sticky', $discussion)),
        ];
    }
}
