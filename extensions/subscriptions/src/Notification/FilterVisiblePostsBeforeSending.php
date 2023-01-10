<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Notification;

use Flarum\Notification\Blueprint\BlueprintInterface;

class FilterVisiblePostsBeforeSending
{
    public function __invoke(BlueprintInterface $blueprint, array $recipients): array
    {
        if ($blueprint instanceof NewPostBlueprint) {
            $newRecipients = [];

            // Flarum has built-in access control for the notification subject,
            // but subscriptions post notifications has the discussion as the subject.
            // We'll add a post visibility check so that users can't get access to hidden replies by subscribing.
            foreach ($recipients as $recipient) {
                if ($blueprint->post->isVisibleTo($recipient)) {
                    $newRecipients[] = $recipient;
                }
            }

            return $newRecipients;
        }

        return $recipients;
    }
}
