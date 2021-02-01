<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Filter;

use Flarum\Discussion\Discussion;
use Flarum\Discussion\Filter\AuthorFilterGambit;
use Flarum\Discussion\Filter\CreatedFilterGambit;
use Flarum\Discussion\Filter\HiddenFilterGambit;
use Flarum\Discussion\Filter\UnreadFilterGambit;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Foundation\ContainerUtil;
use Flarum\User\Filter\EmailFilterGambit;
use Flarum\User\Filter\GroupFilterGambit;
use Flarum\User\User;

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
                Discussion::class => [
                    AuthorFilterGambit::class,
                    CreatedFilterGambit::class,
                    HiddenFilterGambit::class,
                    UnreadFilterGambit::class,
                ],
                User::class => [
                    EmailFilterGambit::class,
                    GroupFilterGambit::class,
                ]
            ];
        });

        $this->app->singleton('flarum.filter.filter_mutators', function () {
            return [];
        });
    }

    public function boot()
    {
        $this->app
            ->when(Filterer::class)
            ->needs('$filters')
            ->give(function () {
                $compiled = [];

                foreach ($this->app->make('flarum.filter.filters') as $resourceClass => $filters) {
                    $compiled[$resourceClass] = [];
                    foreach ($filters as $filter) {
                        $filter = $this->app->make($filter);
                        $compiled[$resourceClass][$filter->getFilterKey()][] = $filter;
                    }
                }

                return $compiled;
            });

        $this->app
            ->when(Filterer::class)
            ->needs('$filterMutators')
            ->give(function () {
                return array_map(function ($resourceFilters) {
                    return array_map(function ($filterClass) {
                        return ContainerUtil::wrapCallback($filterClass, $this->app);
                    }, $resourceFilters);
                }, $this->app->make('flarum.filter.filter_mutators'));
            });
    }
}
