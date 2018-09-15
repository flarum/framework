<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Event\ConfigureUserPreferences;
use Flarum\Event\GetPermission;
use Flarum\Foundation\AbstractServiceProvider;
use Illuminate\Contracts\Container\Container;
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
    }

    protected function registerGate()
    {
        $this->app->singleton('flarum.gate', function ($app) {
            return new Gate($app, function () {
                throw new RuntimeException('You must set the gate user with forUser()');
            });
        });

        $this->app->alias('flarum.gate', 'Illuminate\Contracts\Auth\Access\Gate');
        $this->app->alias('flarum.gate', Gate::class);
    }

    protected function registerAvatarsFilesystem()
    {
        $avatarsFilesystem = function (Container $app) {
            return $app->make('Illuminate\Contracts\Filesystem\Factory')->disk('flarum-avatars')->getDriver();
        };

        $this->app->when(AvatarUploader::class)
            ->needs('League\Flysystem\FilesystemInterface')
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
            if ($actor->isAdmin() || (! $model && $actor->hasPermission($ability))) {
                return true;
            }

            return false;
        });

        User::setHasher($this->app->make('hash'));
        User::setGate($this->app->make('flarum.gate'));

        $events = $this->app->make('events');

        $events->subscribe(SelfDemotionGuard::class);
        $events->subscribe(EmailConfirmationMailer::class);
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
