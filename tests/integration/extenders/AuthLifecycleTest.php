<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Http\AccessToken;
use Flarum\User\AbstractAuthLifecycleHandler;
use Flarum\User\Event;
use Flarum\User\User;
use Flarum\Tests\integration\TestCase;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Illuminate\Contracts\Events\Dispatcher;

class AuthLifecycleTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function prepDb()
    {
        $this->prepareDatabase([
            'users' => [
                $this->adminUser(),
                $this->normalUser(),
            ],
        ]);
    }

    protected function events()
    {
        return $this->app()->getContainer()->make(Dispatcher::class);
    }

    protected function user()
    {
        return User::find(1);
    }

    /**
     * @test
     */
    public function no_effect_if_no_handlers_added()
    {
        $this->prepDb();

        $this->assertEquals($this->user()->username, 'admin');

        $this->events()->dispatch(new Event\Activated($this->user()));
        $this->events()->dispatch(new Event\EmailChanged($this->user()));
        $this->events()->dispatch(new Event\EmailChangeRequested($this->user(), $this->user()->email));
        $this->events()->dispatch(new Event\LoggedIn($this->user(), new AccessToken));
        $this->events()->dispatch(new Event\LoggedOut($this->user()));
        $this->events()->dispatch(new Event\PasswordChanged($this->user()));
        $this->events()->dispatch(new Event\Registered($this->user()));
        $this->events()->dispatch(new Event\RegisteringFromProvider($this->user(), 'custom_sso', []));

        $this->assertEquals($this->user()->username, 'admin');
    }

    /**
     * @test
     */
    public function lifecycle_handler_has_effect()
    {
        $this->extend((new Extend\Auth)->authLifecycleHandler(CustomAuthLifecycleHandler::class));

        $this->prepDb();

        $this->assertEquals($this->user()->username, 'admin');

        $this->events()->dispatch(new Event\Activated($this->user()));

        $this->assertEquals($this->user()->username, 'activated');

        $this->events()->dispatch(new Event\EmailChanged($this->user()));

        $this->assertEquals($this->user()->username, 'emailChanged');

        $this->events()->dispatch(new Event\EmailChangeRequested($this->user(), $this->user()->email));

        $this->assertEquals($this->user()->username, 'emailChangeRequested');

        $this->events()->dispatch(new Event\LoggedIn($this->user(), new AccessToken));

        $this->assertEquals($this->user()->username, 'loggedIn');

        $this->events()->dispatch(new Event\LoggedOut($this->user()));

        $this->assertEquals($this->user()->username, 'loggedOut');

        $this->events()->dispatch(new Event\PasswordChanged($this->user()));

        $this->assertEquals($this->user()->username, 'passwordChanged');

        $this->events()->dispatch(new Event\Registered($this->user()));

        $this->assertEquals($this->user()->username, 'registered');

        $this->events()->dispatch(new Event\RegisteringFromProvider($this->user(), 'custom_sso', []));

        $this->assertEquals($this->user()->username, 'registeringFromProvider');

    }
}

class CustomAuthLifecycleHandler extends AbstractAuthLifecycleHandler
{
    public function whenUserActivated($user, $actor)
    {
        $user->username = 'activated';
        $user->save();
    }

    public function whenUserEmailChanged($user, $actor)
    {
        $user->username = 'emailChanged';
        $user->save();
    }

    public function whenUserEmailChangeRequested($user, $email)
    {
        $user->username = 'emailChangeRequested';
        $user->save();
    }

    public function whenUserLoggedIn($user, $token)
    {
        $user->username = 'loggedIn';
        $user->save();
    }

    public function whenUserLoggedOut($user)
    {
        $user->username = 'loggedOut';
        $user->save();
    }

    public function whenUserPasswordChanged($user, $actor)
    {
        $user->username = 'passwordChanged';
        $user->save();
    }

    public function whenUserRegistered($user, $actor)
    {
        $user->username = 'registered';
        $user->save();
    }

    public function whenUserRegisteringFromProvider($user, $provider, $payload)
    {
        $user->username = 'registeringFromProvider';
        $user->save();
    }
}