<?php namespace Flarum\Core;

use Illuminate\Bus\Dispatcher as Bus;
use Illuminate\Contracts\Container\Container;
use Flarum\Support\ServiceProvider;
use Flarum\Core\Models\CommentPost;
use Flarum\Core\Models\Post;
use Flarum\Core\Models\Model;
use Flarum\Core\Models\Forum;
use Flarum\Core\Models\User;
use Flarum\Core\Models\Discussion;
use Flarum\Core\Search\GambitManager;
use Flarum\Extend;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../../views', 'flarum');

        $this->addEventHandlers();
        $this->bootModels();
        $this->addPostTypes();
        $this->grantPermissions();
        $this->mapCommandHandlers();
    }

    public function mapCommandHandlers()
    {
        $this->app->make(Bus::class)->mapUsing(function ($command) {
            return Bus::simpleMapping(
                $command,
                'Flarum\Core\Commands',
                'Flarum\Core\Handlers\Commands'
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
        // Register a singleton entity that represents this forum. This entity
        // will be used to check for global forum permissions (like viewing the
        // forum, registering, and starting discussions).
        $this->app->singleton('flarum.forum', 'Flarum\Core\Models\Forum');

        // TODO: probably use Illuminate's AggregateServiceProvider
        // functionality, because it includes the 'provides' stuff.
        $this->app->register('Flarum\Core\Activity\ActivityServiceProvider');
        $this->app->register('Flarum\Core\Formatter\FormatterServiceProvider');
        $this->app->register('Flarum\Core\Notifications\NotificationsServiceProvider');

        // TODO: refactor these into the appropriate service providers, when
        // (if) we restructure our namespaces per-entity
        // (Flarum\Core\Discussions\DiscussionsServiceProvider, etc.)
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
            'Flarum\Core\Search\Discussions\Fulltext\DriverInterface',
            'Flarum\Core\Search\Discussions\Fulltext\MySqlFulltextDriver'
        );

        $this->registerDiscussionGambits();
        $this->registerUserGambits();
        $this->registerAvatarsFilesystem();
    }

    public function registerAvatarsFilesystem()
    {
        $avatarsFilesystem = function (Container $app) {
            return $app->make('Illuminate\Contracts\Filesystem\Factory')->disk('flarum-avatars')->getDriver();
        };

        $this->app->when('Flarum\Core\Handlers\Commands\UploadAvatarCommandHandler')
            ->needs('League\Flysystem\FilesystemInterface')
            ->give($avatarsFilesystem);

        $this->app->when('Flarum\Core\Handlers\Commands\DeleteAvatarCommandHandler')
            ->needs('League\Flysystem\FilesystemInterface')
            ->give($avatarsFilesystem);
    }

    public function registerDiscussionGambits()
    {
        $this->app->instance('flarum.discussionGambits', [
            'Flarum\Core\Search\Discussions\Gambits\AuthorGambit',
            'Flarum\Core\Search\Discussions\Gambits\UnreadGambit'
        ]);

        $this->app->when('Flarum\Core\Search\Discussions\DiscussionSearcher')
            ->needs('Flarum\Core\Search\GambitManager')
            ->give(function (Container $app) {
                $gambits = new GambitManager($app);

                foreach ($app->make('flarum.discussionGambits') as $gambit) {
                    $gambits->add($gambit);
                }

                $gambits->setFulltextGambit('Flarum\Core\Search\Discussions\Gambits\FulltextGambit');

                return $gambits;
            });
    }

    public function registerUserGambits()
    {
        $this->app->instance('flarum.userGambits', []);

        $this->app->when('Flarum\Core\Search\Users\UserSearcher')
            ->needs('Flarum\Core\Search\GambitManager')
            ->give(function (Container $app) {
                $gambits = new GambitManager($app);

                foreach ($app->make('flarum.userGambits') as $gambit) {
                    $gambits->add($gambit);
                }

                $gambits->setFulltextGambit('Flarum\Core\Search\Users\Gambits\FulltextGambit');

                return $gambits;
            });
    }

    public function addPostTypes()
    {
        $this->extend([
            new Extend\PostType('Flarum\Core\Models\CommentPost'),
            new Extend\PostType('Flarum\Core\Models\DiscussionRenamedPost')
        ]);
    }

    public function addEventHandlers()
    {
        $this->extend([
            new Extend\EventSubscriber('Flarum\Core\Handlers\Events\DiscussionMetadataUpdater'),
            new Extend\EventSubscriber('Flarum\Core\Handlers\Events\UserMetadataUpdater'),
            new Extend\EventSubscriber('Flarum\Core\Handlers\Events\EmailConfirmationMailer')
        ]);
    }

    public function bootModels()
    {
        Model::setValidator($this->app['validator']);

        CommentPost::setFormatter($this->app['flarum.formatter']);

        User::setHasher($this->app['hash']);
        User::setFormatter($this->app['flarum.formatter']);

        User::registerPreference('discloseOnline', 'boolval', true);
        User::registerPreference('indexProfile', 'boolval', true);
    }

    public function grantPermissions()
    {
        Forum::allow('*', function ($forum, $user, $action) {
            if ($user->hasPermission('forum.'.$action)) {
                return true;
            }
        });

        Post::allow('*', function ($post, $user, $action) {
            if ($post->discussion->can($user, $action.'Posts')) {
                return true;
            }
        });

        // When fetching a discussion's posts: if the user doesn't have permission
        // to moderate the discussion, then they can't see posts that have been
        // hidden by someone other than themself.
        Discussion::addVisiblePostsScope(function ($query, User $user, Discussion $discussion) {
            if (! $discussion->can($user, 'editPosts')) {
                $query->where(function ($query) use ($user) {
                    $query->whereNull('hide_user_id')
                          ->orWhere('hide_user_id', $user->id);
                });
            }
        });

        Post::allow('view', function ($post, $user) {
            if (! $post->hide_user_id || $post->can($user, 'edit')) {
                return true;
            }
        });

        // A post is allowed to be edited if the user has permission to moderate
        // the discussion which it's in, or if they are the author and the post
        // hasn't been deleted by someone else.
        Post::allow('edit', function ($post, $user) {
            if ($post->discussion->can($user, 'editPosts') ||
                ($post->user_id == $user->id && (! $post->hide_user_id || $post->hide_user_id == $user->id))
            ) {
                return true;
            }
        });

        User::allow('*', function ($discussion, $user, $action) {
            if ($user->hasPermission('user.'.$action)) {
                return true;
            }
        });

        User::allow(['edit', 'delete'], function ($user, $actor) {
            if ($user->id == $actor->id) {
                return true;
            }
        });

        Discussion::allow('*', function ($discussion, $user, $action) {
            if ($user->hasPermission('discussion.'.$action)) {
                return true;
            }
        });

        // Allow a user to rename their own discussion.
        Discussion::allow('rename', function ($discussion, $user) {
            if ($discussion->start_user_id == $user->id) {
                return true;
                // @todo add limitations to time etc. according to a config setting
            }
        });

        Discussion::allow('delete', function ($discussion, $user) {
            if ($discussion->start_user_id == $user->id && $discussion->participants_count == 1) {
                return true;
                // @todo add limitations to time etc. according to a config setting
            }
        });
    }
}
