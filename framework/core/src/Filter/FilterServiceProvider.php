<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Filter;

use Flarum\Discussion\Filter\DiscussionFilterer;
use Flarum\Discussion\Filter as DiscussionFilter;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\ContainerUtil;
use Flarum\Group\Filter as GroupFilter;
use Flarum\Group\Filter\GroupFilterer;
use Flarum\Http\Filter as HttpFilter;
use Flarum\Http\Filter\AccessTokenFilterer;
use Flarum\Post\Filter as PostFilter;
use Flarum\Post\Filter\PostFilterer;
use Flarum\User\Filter\UserFilterer;
use Flarum\User\Filter as UserFilter;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;

class FilterServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton('flarum.filter.filters', function () {
            return [
                AccessTokenFilterer::class => [
                    HttpFilter\UserFilter::class,
                ],
                DiscussionFilterer::class => [
                    DiscussionFilter\AuthorFilter::class,
                    DiscussionFilter\CreatedFilter::class,
                    DiscussionFilter\HiddenFilter::class,
                    DiscussionFilter\UnreadFilter::class,
                ],
                UserFilterer::class => [
                    UserFilter\EmailFilter::class,
                    UserFilter\GroupFilter::class,
                ],
                GroupFilterer::class => [
                    GroupFilter\HiddenFilter::class,
                ],
                PostFilterer::class => [
                    PostFilter\AuthorFilter::class,
                    PostFilter\DiscussionFilter::class,
                    PostFilter\IdFilter::class,
                    PostFilter\NumberFilter::class,
                    PostFilter\TypeFilter::class
                ],
            ];
        });

        $this->container->singleton('flarum.filter.filter_mutators', function () {
            return [];
        });
    }

    public function boot(Container $container): void
    {
        // We can resolve the filter mutators in the when->needs->give callback,
        // but we need to resolve at least one regardless so we know which
        // filterers we need to register filters for.
        $filters = $this->container->make('flarum.filter.filters');

        foreach ($filters as $filterer => $filterClasses) {
            $container
                ->when($filterer)
                ->needs('$filters')
                ->give(function () use ($filterClasses) {
                    $compiled = [];

                    foreach ($filterClasses as $filterClass) {
                        $filter = $this->container->make($filterClass);
                        $compiled[$filter->getFilterKey()][] = $filter;
                    }

                    return $compiled;
                });

            $container
                ->when($filterer)
                ->needs('$filterMutators')
                ->give(function () use ($container, $filterer) {
                    return array_map(function ($filterMutatorClass) {
                        return ContainerUtil::wrapCallback($filterMutatorClass, $this->container);
                    }, Arr::get($container->make('flarum.filter.filter_mutators'), $filterer, []));
                });
        }
    }
}
