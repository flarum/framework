<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Users;

use Flarum\Core\Search\GambitManager;
use Flarum\Events\ModelAllow;
use Flarum\Events\RegisterUserGambits;
use Flarum\Events\RegisterUserPreferences;
use Flarum\Support\ServiceProvider;
use Illuminate\Contracts\Container\Container;

class UsersServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        User::setHasher($this->app->make('hash'));
        User::setValidator($this->app->make('validator'));

        $events = $this->app->make('events');

        $events->listen(RegisterUserPreferences::class, function (RegisterUserPreferences $event) {
            $event->register('discloseOnline', 'boolval', true);
            $event->register('indexProfile', 'boolval', true);
            $event->register('locale');
        });

        $events->listen(ModelAllow::class, function (ModelAllow $event) {
            if ($event->model instanceof User) {
                if ($event->actor->hasPermission('user.'.$event->action)) {
                    return true;
                }
            }
        });

        $events->subscribe('Flarum\Core\Users\Listeners\UserMetadataUpdater');
        $events->subscribe('Flarum\Core\Users\Listeners\EmailConfirmationMailer');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerAvatarsFilesystem();
        $this->registerGambits();
    }

    public function registerAvatarsFilesystem()
    {
        $avatarsFilesystem = function (Container $app) {
            return $app->make('Illuminate\Contracts\Filesystem\Factory')->disk('flarum-avatars')->getDriver();
        };

        $this->app->when('Flarum\Core\Users\Commands\UploadAvatarHandler')
            ->needs('League\Flysystem\FilesystemInterface')
            ->give($avatarsFilesystem);

        $this->app->when('Flarum\Core\Users\Commands\DeleteAvatarHandler')
            ->needs('League\Flysystem\FilesystemInterface')
            ->give($avatarsFilesystem);
    }

    public function registerGambits()
    {
        $this->app->when('Flarum\Core\Users\Search\UserSearcher')
            ->needs('Flarum\Core\Search\GambitManager')
            ->give(function (Container $app) {
                $gambits = new GambitManager($app);
                $gambits->setFulltextGambit('Flarum\Core\Users\Search\Gambits\FulltextGambit');

                event(new RegisterUserGambits($gambits));

                return $gambits;
            });
    }
}
