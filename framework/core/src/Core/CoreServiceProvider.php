<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core;

use Flarum\Post\CommentPost;
use Flarum\Event\ConfigurePostTypes;
use Flarum\Event\ConfigureUserPreferences;
use Flarum\Event\GetPermission;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Post\Post;
use Flarum\User\Gate;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

class CoreServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton('flarum.gate', function ($app) {
            return new Gate($app, function () {
                throw new RuntimeException('You must set the gate user with forUser()');
            });
        });

        $this->app->alias('flarum.gate', 'Illuminate\Contracts\Auth\Access\Gate');
        $this->app->alias('flarum.gate', 'Flarum\User\Gate');

        $this->registerAvatarsFilesystem();

        $this->app->register('Flarum\Core\Notification\NotificationServiceProvider');
        $this->app->register('Flarum\Search\SearchServiceProvider');
        $this->app->register('Flarum\Formatter\FormatterServiceProvider');
    }

    protected function registerAvatarsFilesystem()
    {
        $avatarsFilesystem = function (Container $app) {
            return $app->make('Illuminate\Contracts\Filesystem\Factory')->disk('flarum-avatars')->getDriver();
        };

        $this->app->when('Flarum\Core\Command\UploadAvatarHandler')
            ->needs('League\Flysystem\FilesystemInterface')
            ->give($avatarsFilesystem);

        $this->app->when('Flarum\Core\Command\DeleteAvatarHandler')
            ->needs('League\Flysystem\FilesystemInterface')
            ->give($avatarsFilesystem);

        $this->app->when('Flarum\Core\Command\RegisterUserHandler')
            ->needs('League\Flysystem\FilesystemInterface')
            ->give($avatarsFilesystem);
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../../views', 'flarum');

        $this->app->make('Illuminate\Contracts\Bus\Dispatcher')->mapUsing(function ($command) {
            return get_class($command).'Handler@handle';
        });

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

        $this->registerPostTypes();

        CommentPost::setFormatter($this->app->make('flarum.formatter'));

        User::setHasher($this->app->make('hash'));
        User::setGate($this->app->make('flarum.gate'));

        $events = $this->app->make('events');

        $events->subscribe('Flarum\Core\Listener\SelfDemotionGuard');
        $events->subscribe('Flarum\Discussion\DiscussionMetadataUpdater');
        $events->subscribe('Flarum\User\UserMetadataUpdater');
        $events->subscribe('Flarum\Core\Listener\ExtensionValidator');
        $events->subscribe('Flarum\User\EmailConfirmationMailer');
        $events->subscribe('Flarum\Discussion\DiscussionRenamedNotifier');

        $events->subscribe('Flarum\Discussion\DiscussionPolicy');
        $events->subscribe('Flarum\Core\Access\GroupPolicy');
        $events->subscribe('Flarum\Post\PostPolicy');
        $events->subscribe('Flarum\User\UserPolicy');

        $events->listen(ConfigureUserPreferences::class, [$this, 'configureUserPreferences']);
    }

    public function registerPostTypes()
    {
        $models = [
            'Flarum\Post\Post\CommentPost',
            'Flarum\Post\Post\DiscussionRenamedPost'
        ];

        $this->app->make('events')->fire(
            new ConfigurePostTypes($models)
        );

        foreach ($models as $model) {
            Post::setModel($model::$type, $model);
        }
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
