<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\User\User;

class UserResourceFields
{
    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('canSuspend')
                ->get($canSuspend = fn (User $user, Context $context) => $context->getActor()->can('suspend', $user)),
            Schema\Str::make('suspendReason')
                ->description('The reason for the suspension, visible to moderators only.')
                ->writable($canSuspend)
                ->visible($canSuspend)
                ->nullable(),
            Schema\Str::make('suspendMessage')
                ->description('The message shown to the suspended user.')
                ->writable($canSuspend)
                ->visible(fn (User $user, Context $context) => $context->getActor()->id === $user->id || $canSuspend($user, $context))
                ->nullable(),
            Schema\DateTime::make('suspendedUntil')
                ->description('When the suspension ends, or null when the user is not suspended. A date far in the future suspends indefinitely.')
                ->writable($canSuspend)
                ->visible(fn (User $user, Context $context) => $context->getActor()->id === $user->id || $canSuspend($user, $context))
                ->nullable(),
        ];
    }
}
