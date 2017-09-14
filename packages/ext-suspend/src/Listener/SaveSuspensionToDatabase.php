<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Suspend\Listener;

use DateTime;
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
     * @param SuspendValidator $validator
     */
    public function __construct(SuspendValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(Saving::class, [$this, 'whenSavingUser']);
    }

    /**
     * @param Saving $event
     */
    public function whenSavingUser(Saving $event)
    {
        $attributes = array_get($event->data, 'attributes', []);

        if (array_key_exists('suspendUntil', $attributes)) {
            $this->validator->assertValid($attributes);

            $user = $event->user;
            $actor = $event->actor;

            $this->assertCan($actor, 'suspend', $user);

            $user->suspend_until = new DateTime($attributes['suspendUntil']);
        }
    }
}
