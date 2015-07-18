<?php namespace Flarum\Core\Discussions;

use Flarum\Core\Search\GambitManager;
use Flarum\Core\Users\User;
use Flarum\Events\ModelAllow;
use Flarum\Events\RegisterDiscussionGambits;
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
        Discussion::setValidator($this->app->make('validator'));

        $events = $this->app->make('events');

        $events->subscribe('Flarum\Core\Discussions\Listeners\DiscussionMetadataUpdater');

        $events->listen(ModelAllow::class, function (ModelAllow $event) {
            if ($event->model instanceof Discussion) {
                if ($event->action === 'rename' &&
                    $event->model->start_user_id == $event->actor->id) {
                    return true;
                }

                if ($event->action === 'delete' &&
                    $event->model->start_user_id == $event->actor->id &&
                    $event->model->participants_count == 1) {
                    return true;
                }

                if ($event->actor->hasPermission('discussion.'.$event->action)) {
                    return true;
                }
            }
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
            'Flarum\Core\Discussions\Search\Fulltext\Driver',
            'Flarum\Core\Discussions\Search\Fulltext\MySqlFulltextDriver'
        );

        $this->app->when('Flarum\Core\Discussions\Search\DiscussionSearcher')
            ->needs('Flarum\Core\Search\GambitManager')
            ->give(function (Container $app) {
                $gambits = new GambitManager($app);
                $gambits->setFulltextGambit('Flarum\Core\Discussions\Search\Gambits\FulltextGambit');
                $gambits->add('Flarum\Core\Discussions\Search\Gambits\AuthorGambit');
                $gambits->add('Flarum\Core\Discussions\Search\Gambits\UnreadGambit');

                event(new RegisterDiscussionGambits($gambits));

                return $gambits;
            });
    }
}
