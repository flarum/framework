<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Discussions;

use Flarum\Core\Search\GambitManager;
use Flarum\Core\Users\User;
use Flarum\Events\ModelAllow;
use Flarum\Events\ScopeModelVisibility;
use Flarum\Events\RegisterDiscussionGambits;
use Flarum\Events\ScopeEmptyDiscussionVisibility;
use Flarum\Support\ServiceProvider;
use Flarum\Extend;
use Illuminate\Contracts\Container\Container;
use Carbon\Carbon;

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
        $settings = $this->app->make('Flarum\Core\Settings\SettingsRepository');

        $events->subscribe('Flarum\Core\Discussions\Listeners\DiscussionMetadataUpdater');

        $events->listen(ModelAllow::class, function (ModelAllow $event) use ($settings) {
            if ($event->model instanceof Discussion) {
                if ($event->actor->hasPermission('discussion.'.$event->action)) {
                    return true;
                }

                if (($event->action === 'rename' || $event->action === 'delete') &&
                    $event->model->start_user_id == $event->actor->id) {
                    $allowRenaming = $settings->get('allow_renaming');

                    if ($allowRenaming === '-1' ||
                        ($allowRenaming === 'reply' && $event->model->participants_count == 1) ||
                        ($event->model->start_time->diffInMinutes(Carbon::now()) < $allowRenaming)) {
                        return true;
                    }
                }
            }
        });

        $events->listen(ScopeModelVisibility::class, function (ScopeModelVisibility $event) {
            if ($event->model instanceof Discussion) {
                if (! $event->actor->hasPermission('discussion.editPosts')) {
                    $event->query->where(function ($query) use ($event) {
                        $query->where('comments_count', '>', '0')
                            ->orWhere('start_user_id', $event->actor->id);

                        event(new ScopeEmptyDiscussionVisibility($query, $event->actor));
                    });
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
