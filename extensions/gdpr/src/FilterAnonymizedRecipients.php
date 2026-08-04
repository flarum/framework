<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\User\User;

/**
 * Removes anonymized (erased) users from notification recipients. Their email
 * address is a non-routable placeholder and they have no reason to receive
 * notifications, so mailing them only wastes sends and risks bounces.
 */
class FilterAnonymizedRecipients
{
    /**
     * @param User[] $recipients
     * @return User[]
     */
    public function __invoke(BlueprintInterface $blueprint, array $recipients): array
    {
        return array_values(array_filter($recipients, fn (User $user) => ! $user->anonymized));
    }
}
