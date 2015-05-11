<?php namespace Flarum\Core;

use Illuminate\Bus\Dispatcher as Bus;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Flarum\Core\Formatter\FormatterManager;
use Flarum\Core\Models\CommentPost;
use Flarum\Core\Models\Post;
use Flarum\Core\Models\Model;
use Flarum\Core\Models\Forum;
use Flarum\Core\Models\User;
use Flarum\Core\Models\Discussion;
use Flarum\Core\Search\GambitManager;
use League\Flysystem\Adapter\Local;
use Flarum\Core\Events\RegisterDiscussionGambits;
use Flarum\Core\Events\RegisterUserGambits;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(Dispatcher $events, Bus $bus)
    {
        $this->loadViewsFrom(__DIR__.'/../../views', 'flarum');

        $this->registerEventHandlers($events);
        $this->registerPostTypes();
        $this->registerPermissions();
        $this->registerGambits();
        $this->setupModels();

        $this->app['flarum.formatter']->add('linkify', 'Flarum\Core\Formatter\LinkifyFormatter');

        $bus->mapUsing(function ($command) {
            return Bus::simpleMapping(
                $command, 'Flarum\Core\Commands', 'Flarum\Core\Handlers\Commands'
            );
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register('Flarum\Core\Notifications\NotificationServiceProvider');

        // Register a singleton entity that represents this forum. This entity
        // will be used to check for global forum permissions (like viewing the
        // forum, registering, and starting discussions.)
        $this->app->singleton('flarum.forum', 'Flarum\Core\Models\Forum');

        $this->app->singleton('flarum.formatter', 'Flarum\Core\Formatter\FormatterManager');

        $this->app->bind(
            'Flarum\Core\Repositories\DiscussionRepositoryInterface',
            'Flarum\Core\Repositories\EloquentDiscussionRepository'
        );
        $this->app->bind(
            'Flarum\Core\Repositories\PostRepositoryInterface',
            'Flarum\Core\Repositories\EloquentPostRepository'
        );
        $this->app->bind(
            'Flarum\Core\Repositories\UserRepositoryInterface',
            'Flarum\Core\Repositories\EloquentUserRepository'
        );
        $this->app->bind(
            'Flarum\Core\Repositories\ActivityRepositoryInterface',
            'Flarum\Core\Repositories\EloquentActivityRepository'
        );

        $this->app->when('Flarum\Core\Handlers\Commands\UploadAvatarCommandHandler')
            ->needs('League\Flysystem\FilesystemInterface')
            ->give(function(Container $app) {
                return $app->make('Illuminate\Contracts\Filesystem\Factory')->disk('flarum-avatars')->getDriver();
            });
    }

    public function registerGambits()
    {
        $this->app->when('Flarum\Core\Search\Discussions\DiscussionSearcher')
            ->needs('Flarum\Core\Search\GambitManager')
            ->give(function () {
                $gambits = new GambitManager($this->app);
                $gambits->add('Flarum\Core\Search\Discussions\Gambits\AuthorGambit');
                $gambits->add('Flarum\Core\Search\Discussions\Gambits\UnreadGambit');
                $gambits->setFulltextGambit('Flarum\Core\Search\Discussions\Gambits\FulltextGambit');

                event(new RegisterDiscussionGambits($gambits));

                return $gambits;
            });

        $this->app->when('Flarum\Core\Search\Users\UserSearcher')
            ->needs('Flarum\Core\Search\GambitManager')
            ->give(function () {
                $gambits = new GambitManager($this->app);
                $gambits->setFulltextGambit('Flarum\Core\Search\Users\Gambits\FulltextGambit');

                event(new RegisterUserGambits($gambits));

                return $gambits;
            });
    }

    public function registerPostTypes()
    {
        Post::addType('Flarum\Core\Models\CommentPost');
        Post::addType('Flarum\Core\Models\DiscussionRenamedPost');

        CommentPost::setFormatter($this->app['flarum.formatter']);
    }

    public function registerEventHandlers($events)
    {
        $events->subscribe('Flarum\Core\Handlers\Events\DiscussionMetadataUpdater');
        $events->subscribe('Flarum\Core\Handlers\Events\UserMetadataUpdater');
        $events->subscribe('Flarum\Core\Handlers\Events\EmailConfirmationMailer');
    }

    public function setupModels()
    {
        Model::setForum($this->app['flarum.forum']);
        Model::setValidator($this->app['validator']);

        User::setHasher($this->app['hash']);
        User::setFormatter($this->app['flarum.formatter']);

        User::registerPreference('discloseOnline', 'boolval', true);
        User::registerPreference('indexProfile', 'boolval', true);
    }

    public function registerPermissions()
    {
        Forum::grantPermission(function ($grant, $user, $permission) {
            return $user->hasPermission($permission, 'forum');
        });

        Post::grantPermission(function ($grant, $user, $permission) {
            return $user->hasPermission($permission, 'post');
        });

        // Grant view access to a post only if the user can also view the
        // discussion which the post is in. Also, the if the post is hidden,
        // the user must have edit permissions too.
        Post::grantPermission('view', function ($grant) {
            $grant->whereCan('view', 'discussion');
        });

        Post::demandPermission('view', function ($demand) {
            $demand->whereNull('hide_user_id')
                   ->orWhereCan('edit');
        });

        // Allow a user to edit their own post, unless it has been hidden by
        // someone else.
        Post::grantPermission('edit', function ($grant, $user) {
            $grant->whereCan('editOwn')
                  ->where('user_id', $user->id);
        });

        Post::demandPermission('editOwn', function ($demand, $user) {
            $demand->whereNull('hide_user_id');
            if ($user) {
                $demand->orWhere('hide_user_id', $user->id);
            }
        });

        User::grantPermission(function ($grant, $user, $permission) {
            return $user->hasPermission($permission, 'forum');
        });

        // Grant view access to a user if the user can view the forum.
        User::grantPermission('view', function ($grant, $user) {
            $grant->whereCan('view', 'forum');
        });

        // Allow a user to edit their own account.
        User::grantPermission('edit', function ($grant, $user) {
            $grant->where('id', $user->id);
        });

        Discussion::grantPermission(function ($grant, $user, $permission) {
            return $user->hasPermission($permission, 'discussion');
        });

        // Grant view access to a discussion if the user can view the forum.
        Discussion::grantPermission('view', function ($grant, $user) {
            $grant->whereCan('view', 'forum');
        });

        // Allow a user to edit their own discussion.
        Discussion::grantPermission('edit', function ($grant, $user) {
            if ($user->hasPermission('editOwn', 'discussion')) {
                $grant->where('start_user_id', $user->id);
            }
        });
    }
}
