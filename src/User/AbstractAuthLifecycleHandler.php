<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\User\Event;
use Illuminate\Contracts\Events\Dispatcher;

class AbstractAuthLifecycleHandler
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(Event\Activated::class, [$this, 'whenUserActivatedHandler']);
        $events->listen(Event\EmailChanged::class, [$this, 'whenUserEmailChangedHandler']);
        $events->listen(Event\EmailChangeRequested::class, [$this, 'whenUserEmailChangeRequestedHandler']);
        $events->listen(Event\LoggedIn::class, [$this, 'whenUserLoggedInHandler']);
        $events->listen(Event\LoggedOut::class, [$this, 'whenUserLoggedOutHandler']);
        $events->listen(Event\PasswordChanged::class, [$this, 'whenUserPasswordChangedHandler']);
        $events->listen(Event\Registered::class, [$this, 'whenUserRegisteredHandler']);
        $events->listen(Event\RegisteringFromProvider::class, [$this, 'whenUserRegisteringFromProviderHandler']);
    }

    public function whenUserActivatedHandler(Event\Activated $event)
    {
        if (method_exists($this, 'whenUserActivated')) {
            call_user_func_array([$this, 'whenUserActivated'], [$event->user, $event->actor]);
        }
    }

    public function whenUserEmailChangedHandler(Event\EmailChanged $event)
    {
        if (method_exists($this, 'whenUserEmailChanged')) {
            call_user_func_array([$this, 'whenUserEmailChanged'], [$event->user, $event->actor]);
        }
    }

    public function whenUserEmailChangeRequestedHandler(Event\EmailChangeRequested $event)
    {
        if (method_exists($this, 'whenUserEmailChangeRequested')) {
            call_user_func_array([$this, 'whenUserEmailChangeRequested'], [$event->user, $event->actor]);
        }
    }

    public function whenUserLoggedInHandler(Event\LoggedIn $event)
    {
        if (method_exists($this, 'whenUserLoggedIn')) {
            call_user_func_array([$this, 'whenUserLoggedIn'], [$event->user, $event->token]);
        }
    }

    public function whenUserLoggedOutHandler(Event\LoggedOut $event)
    {
        if (method_exists($this, 'whenUserLoggedOut')) {
            call_user_func_array([$this, 'whenUserLoggedOut'], [$event->user]);
        }
    }

    public function whenUserPasswordChangedHandler(Event\PasswordChanged $event)
    {
        if (method_exists($this, 'whenUserPasswordChanged')) {
            call_user_func_array([$this, 'whenUserPasswordChanged'], [$event->user, $event->actor]);
        }
    }

    public function whenUserRegisteredHandler(Event\Registered $event)
    {
        if (method_exists($this, 'whenUserRegistered')) {
            call_user_func_array([$this, 'whenUserRegistered'], [$event->user, $event->actor]);
        }
    }

    public function whenUserRegisteringFromProviderHandler(Event\RegisteringFromProvider $event)
    {
        if (method_exists($this, 'whenUserRegisteringFromProvider')) {
            call_user_func_array([$this, 'whenUserRegisteringFromProvider'], [$event->user, $event->provider, $event->payload]);
        }
    }
}
