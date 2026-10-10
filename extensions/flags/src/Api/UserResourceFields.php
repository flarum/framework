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
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class UserResourceFields
{
    public function __construct(protected SettingsRepositoryInterface $settings)
    {
    }

    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('canViewUserFlags')
                ->get(fn (User $user, Context $context) => $context->getActor()->hasPermission('user.viewFlags')),
            Schema\Boolean::make('canFlagUser')
                ->get(fn (User $user, Context $context) => ! $context->getActor()->isGuest()
                    && $context->getActor()->can('flag', $user)
                    && ($context->getActor()->id !== $user->id || $this->settings->get('flarum-flags.can_flag_own'))),
            Schema\Relationship\ToMany::make('flags')
                ->includable()
                // The default inverse "user" is the reporter, not this target.
                ->inverse('targetUser')
                ->visible(fn (User $user, Context $context) => $context->getActor()->hasPermission('user.viewFlags'))
                ->scope(fn (Builder|Relation $query, Context $context) => $query->whereVisibleTo($context->getActor())),
            Schema\Integer::make('newFlagCount')
                ->visible(fn (User $user, Context $context) => $context->getActor()->id === $user->id)
                ->get(function (User $user, Context $context) {
                    $actor = $context->getActor();
                    $query = Flag::whereVisibleTo($actor);

                    if ($time = $actor->read_flags_at) {
                        $query->where('flags.created_at', '>', $time);
                    }

                    return (clone $query)->distinct()->count('flags.post_id')
                        + (clone $query)->distinct()->count('flags.target_user_id');
                }),
        ];
    }
}
