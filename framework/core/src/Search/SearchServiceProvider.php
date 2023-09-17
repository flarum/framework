<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Discussion\Search\Gambit\FulltextGambit as DiscussionFulltextGambit;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\ContainerUtil;
use Flarum\User\Search\Gambit\FulltextGambit as UserFulltextGambit;
use Flarum\User\Search\UserSearcher;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;

class SearchServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('flarum.simple_search.fulltext_gambits', function () {
            return [
                DiscussionSearcher::class => DiscussionFulltextGambit::class,
                UserSearcher::class => UserFulltextGambit::class
            ];
        });

        $this->container->singleton('flarum.simple_search.gambits', function () {
            return [
                // @TODO searcher filters
                DiscussionSearcher::class => [],
                UserSearcher::class => []
            ];
        });

        $this->container->singleton('flarum.simple_search.search_mutators', function () {
            return [];
        });
    }

    public function boot(Container $container): void
    {
        $fullTextGambits = $container->make('flarum.simple_search.fulltext_gambits');

        foreach ($fullTextGambits as $searcher => $fullTextGambitClass) {
            $container
                ->when($searcher)
                ->needs(GambitManager::class)
                ->give(function () use ($container, $searcher, $fullTextGambitClass) {
                    $gambitManager = new GambitManager($container->make($fullTextGambitClass));
                    foreach (Arr::get($container->make('flarum.simple_search.gambits'), $searcher, []) as $gambit) {
                        $gambitManager->add($container->make($gambit));
                    }

                    return $gambitManager;
                });

            $container
                ->when($searcher)
                ->needs('$searchMutators')
                ->give(function () use ($container, $searcher) {
                    $searchMutators = Arr::get($container->make('flarum.simple_search.search_mutators'), $searcher, []);

                    return array_map(function ($mutator) {
                        return ContainerUtil::wrapCallback($mutator, $this->container);
                    }, $searchMutators);
                });
        }
    }
}
