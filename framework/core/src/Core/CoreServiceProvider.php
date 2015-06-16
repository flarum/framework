<?php namespace Flarum\Core;

use Illuminate\Bus\Dispatcher as Bus;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\Support\ServiceProvider;
use Flarum\Core\Models\CommentPost;
use Flarum\Core\Models\Post;
use Flarum\Core\Models\Model;
use Flarum\Core\Models\Forum;
use Flarum\Core\Models\User;
use Flarum\Core\Models\Discussion;
use Flarum\Core\Search\GambitManager;
use Flarum\Core\Events\RegisterDiscussionGambits;
use Flarum\Core\Events\RegisterUserGambits;
use Flarum\Extend\Permission;
use Flarum\Extend\ActivityType;
use Flarum\Extend\NotificationType;
use Flarum\Extend\Locale;

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
                $command,
                'Flarum\Core\Commands',
                'Flarum\Core\Handlers\Commands'
            );
        });

        $events->subscribe('Flarum\Core\Handlers\Events\DiscussionRenamedNotifier');
        $events->subscribe('Flarum\Core\Handlers\Events\UserActivitySyncer');

        $this->extend(
            (new NotificationType('Flarum\Core\Notifications\DiscussionRenamedNotification', 'Flarum\Api\Serializers\DiscussionBasicSerializer'))
                ->enableByDefault('alert'),
            (new ActivityType('Flarum\Core\Activity\PostedActivity', 'Flarum\Api\Serializers\PostBasicSerializer')),
            (new ActivityType('Flarum\Core\Activity\StartedDiscussionActivity', 'Flarum\Api\Serializers\PostBasicSerializer')),
            (new ActivityType('Flarum\Core\Activity\JoinedActivity', 'Flarum\Api\Serializers\UserBasicSerializer'))
        );

        foreach (['en'] as $locale) {
            $dir = __DIR__.'/../../locale/'.$locale;

            $this->extend(
                (new Locale($locale))
                    ->translations($dir.'/translations.yml')
                    ->config($dir.'/config.php')
                    ->js($dir.'/config.js')
            );
        }
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
        // forum, registering, and starting discussions.)
        $this->app->singleton('flarum.forum', 'Flarum\Core\Models\Forum');

        $this->app->singleton('flarum.formatter', 'Flarum\Core\Formatter\FormatterManager');

        $this->app->singleton('flarum.localeManager', 'Flarum\Locale\LocaleManager');

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

        $this->app->bind(
            'Flarum\Core\Search\Discussions\Fulltext\DriverInterface',
            'Flarum\Core\Search\Discussions\Fulltext\MySqlFulltextDriver'
        );

        $avatarFilesystem = function (Container $app) {
            return $app->make('Illuminate\Contracts\Filesystem\Factory')->disk('flarum-avatars')->getDriver();
        };

        $this->app->when('Flarum\Core\Handlers\Commands\UploadAvatarCommandHandler')
            ->needs('League\Flysystem\FilesystemInterface')
            ->give($avatarFilesystem);

        $this->app->when('Flarum\Core\Handlers\Commands\DeleteAvatarCommandHandler')
            ->needs('League\Flysystem\FilesystemInterface')
            ->give($avatarFilesystem);

        $this->app->bind(
            'Flarum\Core\Repositories\NotificationRepositoryInterface',
            'Flarum\Core\Repositories\EloquentNotificationRepository'
        );
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
        $this->extend(
            new Permission('forum.view'),
            new Permission('forum.startDiscussion'),
            new Permission('discussion.reply'),
            new Permission('discussion.editPosts'),
            new Permission('discussion.deletePosts'),
            new Permission('discussion.rename'),
            new Permission('discussion.delete')
        );

        Forum::allow('*', function ($forum, $user, $action) {
            if ($user->hasPermission('forum.'.$action)) {
                return true;
            }
        });

        Post::allow('*', function ($post, $user, $action) {
            if ($user->hasPermission('post.'.$action)) {
                return true;
            }
        });

        // When fetching a discussion's posts: if the user doesn't have permission
        // to moderate the discussion, then they can't see posts that have been
        // hidden by someone other than themself.
        Discussion::scopeVisiblePosts(function ($query, User $user, Discussion $discussion) {
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
    }
}
