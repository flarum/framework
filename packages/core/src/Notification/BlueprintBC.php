<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification;

use Flarum\Notification\Blueprint\BlueprintInterface;

/**
 * A backwards compatibility layer for notification blueprints.
 *
 * Will be removed for Beta 14 in favor of BlueprintInterface::getAttributes().
 *
 * @deprecated
 */
class BlueprintBC
{
    public static function getAttributes(BlueprintInterface $blueprint): array
    {
        if (method_exists($blueprint, 'getAttributes')) {
            return $blueprint->getAttributes();
        } else {
            return [
                'type' => $blueprint::getType(),
                'from_user_id' => ($fromUser = $blueprint->getFromUser()) ? $fromUser->id : null,
                'subject_id' => ($subject = $blueprint->getSubject()) ? $subject->id : null,
                'data' => ($data = $blueprint->getData()) ? json_encode($data) : null
            ];
        }
    }
}
