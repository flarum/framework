<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification\Event;

use Flarum\Notification\AlertableInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\User\User;

/**
 * Dispatched after notification records have been inserted for a blueprint.
 *
 * Unlike the moment a driver is asked to send (which happens before the rows exist), this fires
 * once the rows are committed, so listeners can rely on the notifications being queryable — e.g.
 * via {@see \Flarum\Notification\Notification::scopeMatchingBlueprint()}. Only carries the
 * recipients for whom a row was actually inserted on this run (not those skipped as duplicates).
 */
class Sent
{
    /**
     * @param User[] $recipients The users for whom a notification record was just inserted.
     */
    public function __construct(
        public BlueprintInterface&AlertableInterface $blueprint,
        public array $recipients
    ) {
    }
}
