<?php namespace Flarum\Core\Users;

use Flarum\Core\Search\GambitManager;
use Flarum\Support\ServiceProvider;
use Flarum\Extend;
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
        $this->extend([
            new Extend\EventSubscriber('Flarum\Core\Users\Listeners\UserMetadataUpdater'),
            new Extend\EventSubscriber('Flarum\Core\Users\Listeners\EmailConfirmationMailer')
        ]);

        User::setHasher($this->app->make('hash'));
        User::setFormatter($this->app->make('flarum.formatter'));
        User::setValidator($this->app->make('validator'));

        User::addPreference('discloseOnline', 'boolval', true);
        User::addPreference('indexProfile', 'boolval', true);

        User::allow('*', function (User $user, User $actor, $action) {
            return $actor->hasPermission('user.'.$action) ?: null;
        });

        User::allow(['edit', 'delete'], function (User $user, User $actor) {
            return $user->id == $actor->id ?: null;
        });
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
        $this->app->instance('flarum.userGambits', []);

        $this->app->when('Flarum\Core\Users\Search\UserSearcher')
            ->needs('Flarum\Core\Search\GambitManager')
            ->give(function (Container $app) {
                $gambits = new GambitManager($app);

                foreach ($app->make('flarum.userGambits') as $gambit) {
                    $gambits->add($gambit);
                }

                $gambits->setFulltextGambit('Flarum\Core\Users\Search\Gambits\FulltextGambit');

                return $gambits;
            });
    }
}
