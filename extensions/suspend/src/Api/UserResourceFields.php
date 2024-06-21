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
                ->writable($canSuspend)
                ->visible($canSuspend),
            Schema\Str::make('suspendMessage')
                ->writable($canSuspend)
                ->visible(fn (User $user, Context $context) => $context->getActor()->id === $user->id || $canSuspend($user, $context)),
            Schema\Date::make('suspendedUntil')
                ->writable($canSuspend)
                ->visible(fn (User $user, Context $context) => $context->getActor()->id === $user->id || $canSuspend($user, $context))
                ->nullable(),
        ];
    }
}
