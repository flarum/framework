<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Filter;

use Flarum\Discussion\Discussion;
use Flarum\Discussion\Filter\AuthorFilter;
use Flarum\Discussion\Filter\CreatedFilter;
use Flarum\Discussion\Filter\HiddenFilter;
use Flarum\Discussion\Filter\UnreadFilter;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\User\Filter\EmailFilter;
use Flarum\User\Filter\GroupFilter;
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
                    AuthorFilter::class,
                    CreatedFilter::class,
                    HiddenFilter::class,
                    UnreadFilter::class,
                ],
                User::class => [
                    EmailFilter::class,
                    GroupFilter::class,
                ]
            ];
        });
    }

    public function boot()
    {
        $allFilters = $this->app->make('flarum.filter.filters');

        foreach ($allFilters as $resource => $resourceFilters) {
            foreach ($resourceFilters as $filter) {
                $filter = $this->app->make($filter);
                Filterer::addFilter($resource, $filter);
            }
        }
    }
}
