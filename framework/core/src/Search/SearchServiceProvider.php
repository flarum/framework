<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

use Flarum\Discussion\Filter as DiscussionFilter;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Discussion\Search\FulltextFilter as DiscussionFulltextFilter;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\ContainerUtil;
use Flarum\Group\Filter as GroupFilter;
use Flarum\Group\Filter\GroupSearcher;
use Flarum\Http\Filter as HttpFilter;
use Flarum\Http\Filter\AccessTokenSearcher;
use Flarum\Post\Filter as PostFilter;
use Flarum\Post\Filter\PostSearcher;
use Flarum\User\Filter as UserFilter;
use Flarum\User\Search\FulltextFilter as UserFulltextFilter;
use Flarum\User\Search\UserSearcher;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;

class SearchServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('flarum.simple_search.fulltext_filters', function () {
            return [
                DiscussionSearcher::class => DiscussionFulltextFilter::class,
                UserSearcher::class => UserFulltextFilter::class
            ];
        });

        $this->container->singleton('flarum.simple_search.filters', function () {
            return [
                AccessTokenSearcher::class => [
                    HttpFilter\UserFilter::class,
                ],
                DiscussionSearcher::class => [
                    \Flarum\Discussion\Search\Filter\AuthorFilter::class,
                    \Flarum\Discussion\Search\Filter\CreatedFilter::class,
                    \Flarum\Discussion\Search\Filter\HiddenFilter::class,
                    \Flarum\Discussion\Search\Filter\UnreadFilter::class,
                ],
                UserSearcher::class => [
                    \Flarum\User\Search\Filter\EmailFilter::class,
                    \Flarum\User\Search\Filter\GroupFilter::class,
                ],
                GroupSearcher::class => [
                    GroupFilter\HiddenFilter::class,
                ],
                PostSearcher::class => [
                    PostFilter\AuthorFilter::class,
                    PostFilter\DiscussionFilter::class,
                    PostFilter\IdFilter::class,
                    PostFilter\NumberFilter::class,
                    PostFilter\TypeFilter::class
                ],
            ];
        });

        $this->container->singleton('flarum.simple_search.search_mutators', function () {
            return [];
        });
    }

    public function boot(Container $container): void
    {
        foreach ($container->make('flarum.simple_search.filters') as $searcher => $filterClasses) {
            $container
                ->when($searcher)
                ->needs(FilterManager::class)
                ->give(function () use ($container, $searcher) {
                    $fulltext = $container->make('flarum.simple_search.fulltext_filters');
                    $fulltextClass = $fulltext[$searcher] ?? null;

                    $manager = new FilterManager(
                        $fulltextClass ? $container->make($fulltextClass) : null
                    );

                    foreach (Arr::get($container->make('flarum.simple_search.filters'), $searcher, []) as $filter) {
                        $manager->add($container->make($filter));
                    }

                    return $manager;
                });

            $container
                ->when($searcher)
                ->needs('$mutators')
                ->give(function () use ($container, $searcher) {
                    $searchMutators = Arr::get($container->make('flarum.simple_search.search_mutators'), $searcher, []);

                    return array_map(function ($mutator) {
                        return ContainerUtil::wrapCallback($mutator, $this->container);
                    }, $searchMutators);
                });
        }
    }
}
