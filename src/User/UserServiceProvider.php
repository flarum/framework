<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Event\ConfigureUserPreferences;
use Flarum\Event\GetPermission;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\DisplayName\DriverInterface;
use Flarum\User\DisplayName\UsernameDriver;
use Flarum\User\Event\EmailChangeRequested;
use Flarum\User\Event\Registered;
use Flarum\User\Event\Saving;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Support\Arr;
use League\Flysystem\FilesystemInterface;
use RuntimeException;

class UserServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->registerGate();
        $this->registerAvatarsFilesystem();
        $this->registerDisplayNameDrivers();
    }

    protected function registerDisplayNameDrivers()
    {
        $this->app->singleton('flarum.user.display_name.supported_drivers', function () {
            return [
                'username' => UsernameDriver::class,
            ];
        });

        $this->app->singleton('flarum.user.display_name.driver', function () {
            $drivers = $this->app->make('flarum.user.display_name.supported_drivers');
            $settings = $this->app->make(SettingsRepositoryInterface::class);
            $driverName = $settings->get('display_name_driver', '');

            $driverClass = Arr::get($drivers, $driverName);

            return $driverClass
                ? $this->app->make($driverClass)
                : $this->app->make(UsernameDriver::class);
        });

        $this->app->alias('flarum.user.display_name.driver', DriverInterface::class);
    }

    protected function registerGate()
    {
        $this->app->singleton('flarum.gate', function ($app) {
            return new Gate($app, function () {
                throw new RuntimeException('You must set the gate user with forUser()');
            });
        });

        $this->app->alias('flarum.gate', GateContract::class);
        $this->app->alias('flarum.gate', Gate::class);
    }

    protected function registerAvatarsFilesystem()
    {
        $avatarsFilesystem = function (Container $app) {
            return $app->make(Factory::class)->disk('flarum-avatars')->getDriver();
        };

        $this->app->when(AvatarUploader::class)
            ->needs(FilesystemInterface::class)
            ->give($avatarsFilesystem);
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->app->make('flarum.gate')->before(function (User $actor, $ability, $model = null) {
            // Fire an event so that core and extension policies can hook into
            // this permission query and explicitly grant or deny the
            // permission.
            $allowed = $this->app->make('events')->until(
                new GetPermission($actor, $ability, $model)
            );

            if (! is_null($allowed)) {
                return $allowed;
            }

            // If no policy covered this permission query, we will only grant
            // the permission if the actor's groups have it. Otherwise, we will
            // not allow the user to perform this action.
            if ($actor->isAdmin() || $actor->hasPermission($ability)) {
                return true;
            }

            return false;
        });

        User::setHasher($this->app->make('hash'));
        User::setGate($this->app->make('flarum.gate'));
        User::setDisplayNameDriver($this->app->make('flarum.user.display_name.driver'));

        $events = $this->app->make('events');

        $events->listen(Saving::class, SelfDemotionGuard::class);
        $events->listen(Registered::class, AccountActivationMailer::class);
        $events->listen(EmailChangeRequested::class, EmailConfirmationMailer::class);

        $events->subscribe(UserMetadataUpdater::class);
        $events->subscribe(UserPolicy::class);

        $events->listen(ConfigureUserPreferences::class, [$this, 'configureUserPreferences']);
    }

    /**
     * @param ConfigureUserPreferences $event
     */
    public function configureUserPreferences(ConfigureUserPreferences $event)
    {
        $event->add('discloseOnline', 'boolval', true);
        $event->add('indexProfile', 'boolval', true);
        $event->add('locale');
    }
}
