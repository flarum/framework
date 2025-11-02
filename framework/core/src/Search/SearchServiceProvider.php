<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

use Flarum\Discussion\Discussion;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Discussion\Search\Filter as DiscussionFilter;
use Flarum\Discussion\Search\FulltextFilter as DiscussionFulltextFilter;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\ContainerUtil;
use Flarum\Group\Filter as GroupFilter;
use Flarum\Group\Filter\GroupSearcher;
use Flarum\Group\Group;
use Flarum\Http\AccessToken;
use Flarum\Http\Filter\AccessTokenSearcher;
use Flarum\Http\Filter as HttpFilter;
use Flarum\Post\Filter as PostFilter;
use Flarum\Post\Filter\PostSearcher;
use Flarum\Post\Post;
use Flarum\Search\Filter\FilterManager;
use Flarum\Search\Listener\ModelObserver;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Search\Filter as UserFilter;
use Flarum\User\Search\FulltextFilter as UserFulltextFilter;
use Flarum\User\Search\UserSearcher;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;

class SearchServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('flarum.search', function (Container $container) {
            return new SearchManager(
                array_keys($container->make('flarum.search.drivers')),
                $container->make('flarum.search.indexers'),
                $container->make(SettingsRepositoryInterface::class),
                $container,
            );
        });

        $this->container->alias('flarum.search', SearchManager::class);

        $this->container->singleton('flarum.search.drivers', function () {
            return [
                Database\DatabaseSearchDriver::class => [
                    Discussion::class => DiscussionSearcher::class,
                    User::class => UserSearcher::class,
                    Post::class => PostSearcher::class,
                    Group::class => GroupSearcher::class,
                    AccessToken::class => AccessTokenSearcher::class,
                ],
            ];
        });

        $this->container->singleton('flarum.search.fulltext', function () {
            return [
                DiscussionSearcher::class => DiscussionFulltextFilter::class,
                PostSearcher::class => PostFilter\FulltextFilter::class,
                UserSearcher::class => UserFulltextFilter::class
            ];
        });

        $this->container->singleton('flarum.search.filters', function () {
            return [
                AccessTokenSearcher::class => [
                    HttpFilter\UserFilter::class,
                    HttpFilter\AccessTokenTypeFilter::class
                ],
                DiscussionSearcher::class => [
                    DiscussionFilter\AuthorFilter::class,
                    DiscussionFilter\CreatedFilter::class,
                    DiscussionFilter\HiddenFilter::class,
                    DiscussionFilter\UnreadFilter::class,
                ],
                UserSearcher::class => [
                    UserFilter\EmailFilter::class,
                    UserFilter\GroupFilter::class,
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

        $this->container->singleton('flarum.search.mutators', function () {
            return [];
        });

        // Indexers aren't driver specific.
        // For example, a search driver implementation may support searching discussions,
        // and would need to index discussions for that, but it would also need to index
        // posts without supporting searching them, because it needs to index the posts for
        // searching discussions.
        $this->container->singleton('flarum.search.indexers', function () {
            return [
                // Model::class => [...],
            ];
        });
    }

    public function boot(Container $container): void
    {
        foreach ($container->make('flarum.search.drivers') as $driverClass => $searchers) {
            $container
                ->when($driverClass)
                ->needs('$searchers')
                ->give($searchers);

            foreach ($searchers as $searcher) {
                $container
                    ->when($searcher)
                    ->needs(FilterManager::class)
                    ->give(function () use ($container, $searcher) {
                        $fulltext = $container->make('flarum.search.fulltext');
                        $fulltextClass = $fulltext[$searcher] ?? null;

                        $manager = new FilterManager(
                            $fulltextClass ? $container->make($fulltextClass) : null
                        );

                        foreach (Arr::get($container->make('flarum.search.filters'), $searcher, []) as $filter) {
                            $manager->add($container->make($filter));
                        }

                        return $manager;
                    });

                $container
                    ->when($searcher)
                    ->needs('$mutators')
                    ->give(function () use ($container, $searcher) {
                        $searchMutators = Arr::get($container->make('flarum.search.mutators'), $searcher, []);

                        return array_map(function ($mutator) {
                            return ContainerUtil::wrapCallback($mutator, $this->container);
                        }, $searchMutators);
                    });
            }
        }

        /** @var \Flarum\Database\AbstractModel $modelClass */
        foreach ($container->make('flarum.search.indexers') as $modelClass => $indexers) {
            $modelClass::observe(ModelObserver::class);
        }
    }
}
