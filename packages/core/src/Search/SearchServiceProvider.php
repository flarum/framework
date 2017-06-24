<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Search;

use Flarum\Event\ConfigureDiscussionGambits;
use Flarum\Event\ConfigureUserGambits;
use Flarum\Foundation\AbstractServiceProvider;
use Illuminate\Contracts\Container\Container;

class SearchServiceProvider extends AbstractServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            'Flarum\Discussion\Search\Fulltext\DriverInterface',
            'Flarum\Discussion\Search\Fulltext\MySqlFulltextDriver'
        );

        $this->registerDiscussionGambits();

        $this->registerUserGambits();
    }

    public function registerUserGambits()
    {
        $this->app->when('Flarum\User\Search\UserSearcher')
            ->needs('Flarum\Search\GambitManager')
            ->give(function (Container $app) {
                $gambits = new GambitManager($app);

                $gambits->setFulltextGambit('Flarum\User\Search\Gambit\FulltextGambit');
                $gambits->add('Flarum\User\Search\Gambit\EmailGambit');
                $gambits->add('Flarum\User\Search\Gambit\GroupGambit');

                $app->make('events')->fire(
                    new ConfigureUserGambits($gambits)
                );

                return $gambits;
            });
    }

    public function registerDiscussionGambits()
    {
        $this->app->when('Flarum\Discussion\Search\DiscussionSearcher')
            ->needs('Flarum\Search\GambitManager')
            ->give(function (Container $app) {
                $gambits = new GambitManager($app);

                $gambits->setFulltextGambit('Flarum\Discussion\Search\Gambit\FulltextGambit');
                $gambits->add('Flarum\Discussion\Search\Gambit\AuthorGambit');
                $gambits->add('Flarum\Discussion\Search\Gambit\CreatedGambit');
                $gambits->add('Flarum\Discussion\Search\Gambit\HiddenGambit');
                $gambits->add('Flarum\Discussion\Search\Gambit\UnreadGambit');

                $app->make('events')->fire(
                    new ConfigureDiscussionGambits($gambits)
                );

                return $gambits;
            });
    }
}
