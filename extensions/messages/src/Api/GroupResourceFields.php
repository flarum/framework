<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Api;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Flarum\Group\Group;
use Flarum\Messages\RecipientGroups;

class GroupResourceFields
{
    public function __construct(protected RecipientGroups $groups)
    {
    }

    public function __invoke(): array
    {
        return [Schema\Boolean::make('canMessage')
            ->get(fn (Group $group, Context $context) => $this->groups->canAddress($context->getActor(), $group))];
    }
}
