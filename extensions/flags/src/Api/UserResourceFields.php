<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Flags\Flag;
use Flarum\User\User;

class UserResourceFields
{
    public function __invoke(): array
    {
        return [
            Schema\Integer::make('newFlagCount')
                ->visible(fn (User $user, Context $context) => $context->getActor()->id === $user->id)
                ->get(function (User $user, Context $context) {
                    $actor = $context->getActor();
                    $query = Flag::whereVisibleTo($actor);

                    if ($time = $actor->read_flags_at) {
                        $query->where('flags.created_at', '>', $time);
                    }

                    return $query->distinct()->count('flags.post_id');
                }),
        ];
    }
}
