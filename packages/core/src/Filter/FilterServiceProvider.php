<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Filter;

use Flarum\Discussion\Filter as DiscussionFilter;
use Flarum\Discussion\Filter\DiscussionFilterer;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\ContainerUtil;
use Flarum\Post\Filter as PostFilter;
use Flarum\Post\Filter\PostFilterer;
use Flarum\User\Filter as UserFilter;
use Flarum\User\Filter\UserFilterer;
use Illuminate\Support\Arr;

class FilterServiceProvider extends AbstractServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('flarum.filter.filters', function () {
            return [
                DiscussionFilterer::class => [
                    DiscussionFilter\AuthorFilterGambit::class,
                    DiscussionFilter\CreatedFilterGambit::class,
                    DiscussionFilter\HiddenFilterGambit::class,
                    DiscussionFilter\UnreadFilterGambit::class,
                ],
                UserFilterer::class => [
                    UserFilter\EmailFilterGambit::class,
                    UserFilter\GroupFilterGambit::class,
                ],
                PostFilterer::class => [
                    PostFilter\AuthorFilter::class,
                    PostFilter\DiscussionFilter::class,
                    PostFilter\IdFilter::class,
                    PostFilter\NumberFilter::class,
                    PostFilter\TypeFilter::class,
                ],
            ];
        });

        $this->app->singleton('flarum.filter.filter_mutators', function () {
            return [];
        });
    }

    public function boot()
    {
        // We can resolve the filter mutators in the when->needs->give callback,
        // but we need to resolve at least one regardless so we know which
        // filterers we need to register filters for.
        $filters = $this->app->make('flarum.filter.filters');

        foreach ($filters as $filterer => $filterClasses) {
            $this->app
                ->when($filterer)
                ->needs('$filters')
                ->give(function () use ($filterClasses) {
                    $compiled = [];

                    foreach ($filterClasses as $filterClass) {
                        $filter = $this->app->make($filterClass);
                        $compiled[$filter->getFilterKey()][] = $filter;
                    }

                    return $compiled;
                });

            $this->app
                ->when($filterer)
                ->needs('$filterMutators')
                ->give(function () use ($filterer) {
                    return array_map(function ($filterMutatorClass) {
                        return ContainerUtil::wrapCallback($filterMutatorClass, $this->app);
                    }, Arr::get($this->app->make('flarum.filter.filter_mutators'), $filterer, []));
                });
        }
    }
}
