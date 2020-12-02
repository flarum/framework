<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Discussion\Search\Gambit\AuthorGambit;
use Flarum\Discussion\Search\Gambit\CreatedGambit;
use Flarum\Discussion\Search\Gambit\FulltextGambit as DiscussionFulltextGambit;
use Flarum\Discussion\Search\Gambit\HiddenGambit;
use Flarum\Discussion\Search\Gambit\UnreadGambit;
use Flarum\Event\ConfigureDiscussionGambits;
use Flarum\Event\ConfigureUserGambits;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\User\Search\Gambit\EmailGambit;
use Flarum\User\Search\Gambit\FulltextGambit as UserFulltextGambit;
use Flarum\User\Search\Gambit\GroupGambit;
use Flarum\User\Search\UserSearcher;

class SearchServiceProvider extends AbstractServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerDiscussionGambits();

        $this->registerUserGambits();
    }

    public function registerUserGambits()
    {
        $gambits = AbstractSearcher::gambitManager(UserSearcher::class);
        $gambits->setFullTextGambit($this->app->make(UserFulltextGambit::class));
        $gambits->add($this->app->make(EmailGambit::class));
        $gambits->add($this->app->make(GroupGambit::class));

        // Deprecated BC layer to support the ConfigureGambits events till next version.
        $tempGambits = new GambitManager;
        $this->app->make('events')->dispatch(
            new ConfigureUserGambits($tempGambits)
        );

        if (!is_null($fullTextGambit = $tempGambits->getFullTextGambit())) {
            $gambits->setFullTextGambit($this->app->make($fullTextGambit));
        }

        foreach ($tempGambits->getGambits() as $gambit) {
            $gambits->add($this->app->make($gambit));
        }
    }

    public function registerDiscussionGambits()
    {
        $gambits = AbstractSearcher::gambitManager(DiscussionSearcher::class);
        $gambits->setFullTextGambit($this->app->make(DiscussionFulltextGambit::class));
        $gambits->add($this->app->make(AuthorGambit::class));
        $gambits->add($this->app->make(CreatedGambit::class));
        $gambits->add($this->app->make(HiddenGambit::class));
        $gambits->add($this->app->make(UnreadGambit::class));

        // Deprecated BC layer to support the ConfigureGambits events till next version.
        $tempGambits = new GambitManager;
        $this->app->make('events')->dispatch(
            new ConfigureDiscussionGambits($tempGambits)
        );

        if (!is_null($fullTextGambit = $tempGambits->getFullTextGambit())) {
            $gambits->setFullTextGambit($this->app->make($fullTextGambit));
        }

        foreach ($tempGambits->getGambits() as $gambit) {
            $gambits->add($this->app->make($gambit));
        }
    }
}
