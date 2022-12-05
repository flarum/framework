<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions;

use Flarum\Api\Serializer\CurrentUserSerializer;
use Flarum\User\User;

class AddCurrentUserAttributes
{
    public function __invoke(CurrentUserSerializer $serializer, User $user, array $attributes): array
    {
        $attributes['canMentionGroups'] = $user->can('mentionGroups');

        return $attributes;
    }
}
