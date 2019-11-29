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
use Flarum\User\AssertPermissionTrait;
use Flarum\User\Event\Saving;
use Illuminate\Contracts\Events\Dispatcher;

class SaveSuspensionToDatabase
{
    use AssertPermissionTrait;

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
        $attributes = array_get($event->data, 'attributes', []);

        if (array_key_exists('suspendedUntil', $attributes)) {
            $this->validator->assertValid($attributes);

            $user = $event->user;
            $actor = $event->actor;

            $this->assertCan($actor, 'suspend', $user);

            $user->suspended_until = $attributes['suspendedUntil']
                ? new DateTime($attributes['suspendedUntil'])
                : null;

            if ($user->isDirty('suspended_until')) {
                $this->events->dispatch(
                    $user->suspended_until === null ?
                        new Unsuspended($user, $actor) :
                        new Suspended($user, $actor)
                );
            }
        }
    }
}
