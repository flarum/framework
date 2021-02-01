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
