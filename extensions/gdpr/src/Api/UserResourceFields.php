<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\User\User;

class UserResourceFields
{
    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('anonymized')
                ->visible(fn (User $user, Context $context) => $context->getActor()->can('seeAnonymizedUserBadges')),
            Schema\Boolean::make('canModerateExports')
                ->get(fn (User $user, Context $context) => $context->getActor()->can('exportFor', $user)),

            Schema\Relationship\ToOne::make('erasureRequest')
                ->includable()
                ->type('user-erasure-requests'),
        ];
    }
}
