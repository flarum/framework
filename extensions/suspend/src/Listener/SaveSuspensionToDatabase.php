<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend\Listener;

use DateTime;
use Flarum\Suspend\Event\Suspended;
use Flarum\Suspend\Event\Unsuspended;
use Flarum\Suspend\SuspendValidator;
use Flarum\User\Event\Saving;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;

class SaveSuspensionToDatabase
{
    /**
     * Validator for limited suspension.
     *
     * @var SuspendValidator
     */
    protected $validator;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @param SuspendValidator $validator
     * @param Dispatcher $events
     */
    public function __construct(SuspendValidator $validator, Dispatcher $events)
    {
        $this->validator = $validator;
        $this->events = $events;
    }

    public function handle(Saving $event)
    {
        $attributes = Arr::get($event->data, 'attributes', []);

        if (array_key_exists('suspendedUntil', $attributes)) {
            $this->validator->assertValid($attributes);

            $user = $event->user;
            $actor = $event->actor;

            $actor->assertCan('suspend', $user);

            if ($attributes['suspendedUntil']) {
                $user->suspended_until = new DateTime($attributes['suspendedUntil']);
                $user->suspend_reason = empty($attributes['suspendReason']) ? null : $attributes['suspendReason'];
                $user->suspend_message = empty($attributes['suspendMessage']) ? null : $attributes['suspendMessage'];
            } else {
                $user->suspended_until = null;
                $user->suspend_reason = null;
                $user->suspend_message = null;
            }

            if ($user->isDirty(['suspended_until', 'suspend_reason', 'suspend_message'])) {
                $this->events->dispatch(
                    $user->suspended_until === null ?
                        new Unsuspended($user, $actor) :
                        new Suspended($user, $actor)
                );
            }
        }
    }
}
