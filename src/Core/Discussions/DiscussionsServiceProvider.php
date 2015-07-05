<?php namespace Flarum\Core\Discussions;

use Flarum\Core\Search\GambitManager;
use Flarum\Core\Users\User;
use Flarum\Support\ServiceProvider;
use Flarum\Extend;
use Illuminate\Contracts\Container\Container;

class DiscussionsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->extend([
            new Extend\EventSubscriber('Flarum\Core\Discussions\Listeners\DiscussionMetadataUpdater')
        ]);

        Discussion::setValidator($this->app->make('validator'));

        Discussion::allow('*', function (Discussion $discussion, User $user, $action) {
            return $user->hasPermission('discussion.'.$action) ?: null;
        });

        // Allow a user to rename their own discussion.
        Discussion::allow('rename', function (Discussion $discussion, User $user) {
            return $discussion->start_user_id == $user->id ?: null;
            // TODO: add limitations to time etc. according to a config setting
        });

        Discussion::allow('delete', function (Discussion $discussion, User $user) {
            return $discussion->start_user_id == $user->id && $discussion->participants_count == 1 ?: null;
            // TODO: add limitations to time etc. according to a config setting
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            'Flarum\Core\Discussions\Search\Fulltext\DriverInterface',
            'Flarum\Core\Discussions\Search\Fulltext\MySqlFulltextDriver'
        );

        $this->app->instance('flarum.discussionGambits', [
            'Flarum\Core\Discussions\Search\Gambits\AuthorGambit',
            'Flarum\Core\Discussions\Search\Gambits\UnreadGambit'
        ]);

        $this->app->when('Flarum\Core\Discussions\Search\DiscussionSearcher')
            ->needs('Flarum\Core\Search\GambitManager')
            ->give(function (Container $app) {
                $gambits = new GambitManager($app);

                foreach ($app->make('flarum.discussionGambits') as $gambit) {
                    $gambits->add($gambit);
                }

                $gambits->setFulltextGambit('Flarum\Core\Discussions\Search\Gambits\FulltextGambit');

                return $gambits;
            });
    }
}
