<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

use Flarum\Discussion\Query as DiscussionQuery;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Discussion\Search\Gambit\FulltextGambit as DiscussionFulltextGambit;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\ContainerUtil;
use Flarum\User\Query as UserQuery;
use Flarum\User\Search\Gambit\FulltextGambit as UserFulltextGambit;
use Flarum\User\Search\UserSearcher;
use Illuminate\Support\Arr;

class SearchServiceProvider extends AbstractServiceProvider
{
    /**
     * @inheritDoc
     */
    public function register()
    {
        $this->container->singleton('flarum.simple_search.fulltext_gambits', function () {
            return [
                DiscussionSearcher::class => DiscussionFulltextGambit::class,
                UserSearcher::class => UserFulltextGambit::class
            ];
        });

        $this->container->singleton('flarum.simple_search.gambits', function () {
            return [
                DiscussionSearcher::class => [
                    DiscussionQuery\AuthorFilterGambit::class,
                    DiscussionQuery\CreatedFilterGambit::class,
                    DiscussionQuery\HiddenFilterGambit::class,
                    DiscussionQuery\UnreadFilterGambit::class,
                ],
                UserSearcher::class => [
                    UserQuery\EmailFilterGambit::class,
                    UserQuery\GroupFilterGambit::class,
                ]
            ];
        });

        $this->container->singleton('flarum.simple_search.search_mutators', function () {
            return [];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $fullTextGambits = $this->container->make('flarum.simple_search.fulltext_gambits');
        $gambits = $this->container->make('flarum.simple_search.gambits');

        $searchersWithGambitsWithoutFulltext = array_diff(array_keys($gambits), array_keys($fullTextGambits));

        if (count($searchersWithGambitsWithoutFulltext)) {
            $affectedGambits = [];
            foreach ($searchersWithGambitsWithoutFulltext as $searcher) {
                // This check is in place to support adding gambits to searchers
                // registered in extensions that are optional dependencies of the
                // current extension.
                if (class_exists($searcher)) {
                    $affectedGambits += $gambits[$searcher];
                }
            }

            throw new \RuntimeException('You cannot add gambits to searchers that do not have fulltext gambits. The following searchers have this issue: '.implode(', ', $affectedGambits));
        }

        foreach ($fullTextGambits as $searcher => $fullTextGambitClass) {
            $this->container
                ->when($searcher)
                ->needs(GambitManager::class)
                ->give(function () use ($searcher, $fullTextGambitClass, $gambits) {
                    $gambitManager = new GambitManager($this->container->make($fullTextGambitClass));
                    foreach (Arr::get($gambits, $searcher, []) as $gambit) {
                        $gambitManager->add($this->container->make($gambit));
                    }

                    return $gambitManager;
                });

            $this->container
                ->when($searcher)
                ->needs('$searchMutators')
                ->give(function () use ($searcher) {
                    $searchMutators = Arr::get($this->container->make('flarum.simple_search.search_mutators'), $searcher, []);

                    return array_map(function ($mutator) {
                        return ContainerUtil::wrapCallback($mutator, $this->container);
                    }, $searchMutators);
                });
        }
    }
}
