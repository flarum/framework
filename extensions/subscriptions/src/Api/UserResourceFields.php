<?php

namespace Flarum\Subscriptions\Api;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Schema;
use Flarum\Discussion\Discussion;

class UserResourceFields
{
    public function __invoke(): array
    {
        return [
            Schema\Str::make('subscription')
                ->writable(fn (Discussion $discussion, Context $context) => $context->endpoint instanceof Endpoint\Update && ! $context->getActor()->isGuest())
                ->nullable()
                ->get(fn (Discussion $discussion) => $discussion->state?->subscription)
                ->set(function (Discussion $discussion, ?string $subscription, Context $context) {
                    $actor = $context->getActor();
                    $state = $discussion->stateFor($actor);

                    if (! in_array($subscription, ['follow', 'ignore'])) {
                        $subscription = null;
                    }

                    $state->subscription = $subscription;
                }),
        ];
    }
}
