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
use Flarum\Foundation\ContainerUtil;
use Flarum\User\Search\Gambit\EmailGambit;
use Flarum\User\Search\Gambit\FulltextGambit as UserFulltextGambit;
use Flarum\User\Search\Gambit\GroupGambit;
use Flarum\User\Search\UserSearcher;
use Illuminate\Support\Arr;

class SearchServiceProvider extends AbstractServiceProvider
{
    /**
     * @inheritDoc
     */
    public function register()
    {
        $this->app->singleton('flarum.simple_search.fulltext_gambits', function () {
            return [
                DiscussionSearcher::class => DiscussionFulltextGambit::class,
                UserSearcher::class => UserFulltextGambit::class
            ];
        });

        $this->app->singleton('flarum.simple_search.gambits', function() {
            return [
                DiscussionSearcher::class => [
                    AuthorGambit::class,
                    CreatedGambit::class,
                    HiddenGambit::class,
                    UnreadGambit::class
                ],
                UserSearcher::class => [
                    EmailGambit::class,
                    GroupGambit::class
                ]
            ];
        });

        $this->app->singleton('flarum.simple_search.search_mutators', function () {
            return [];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        // The rest of these we can resolve in the when->needs->give callback,
        // but we need to resolve at least one regardless so we know which
        // searchers we need to register gambits for.
        $fullTextGambits = $this->app->make('flarum.simple_search.fulltext_gambits');

        foreach($fullTextGambits as $searcher => $fullTextGambitClass) {
            $this->app
                ->when($searcher)
                ->needs(GambitManager::class)
                ->give(function() use ($searcher, $fullTextGambitClass) {
                    $gambitManager = new GambitManager();
                    $gambitManager->setFulltextGambit($this->app->make($fullTextGambitClass));
                    foreach (Arr::get($this->app->make('flarum.simple_search.gambits'), $searcher, []) as $gambit) {
                        $gambitManager->add($this->app->make($gambit));
                    }

                    if ($searcher === UserSearcher::class) {
                        $this->app->make('events')->dispatch(
                            new ConfigureUserGambits($gambitManager)
                        );
                    } elseif ($searcher === DiscussionSearcher::class) {
                        $this->app->make('events')->dispatch(
                            new ConfigureDiscussionGambits($gambitManager)
                        );
                    }

                    return $gambitManager;
                });

            $this->app
                ->when($searcher)
                ->needs(SearchMutators::class)
                ->give(function () use ($searcher) {
                    $searchMutators = Arr::get($this->app->make('flarum.simple_search.search_mutators'), $searcher, []);

                    return new SearchMutators(array_map(function($mutator) {
                        return ContainerUtil::wrapCallback($mutator, $this->app);
                    }, $searchMutators));
                });
        }
    }
}
