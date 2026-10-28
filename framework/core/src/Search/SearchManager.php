<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

use Flarum\Database\AbstractModel;
use Flarum\Foundation\ContainerUtil;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\Search\Filter\DescribesFilter;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;

class SearchManager
{
    public function __construct(
        /** @var array<class-string<AbstractDriver>> */
        protected array $drivers,
        /** @var array<class-string<AbstractModel>, array<class-string<IndexerInterface>>> */
        protected array $indexers,
        protected SettingsRepositoryInterface $settings,
        protected Container $container
    ) {
    }

    public function driver(?string $name): AbstractDriver
    {
        $driver = Arr::first($this->drivers, fn ($driver) => $driver::name() === $name);

        if (! $driver) {
            return $this->driver(DatabaseSearchDriver::name());
        }

        return $this->container->make($driver);
    }

    public function driverFor(string $resourceClass): AbstractDriver
    {
        return $this->driver($this->settings->get("search_driver_$resourceClass"));
    }

    /**
     * @param class-string<AbstractModel> $resourceClass
     * @return array<class-string<IndexerInterface>>
     */
    public function indexers(string $resourceClass): array
    {
        return $this->indexers[$resourceClass] ?? [];
    }

    public function indexable(string $resourceClass): bool
    {
        return ! empty($this->indexers[$resourceClass]);
    }

    public function query(string $resourceClass, SearchCriteria $criteria): SearchResults
    {
        $driver = $this->driverFor($resourceClass);
        $defaultDriver = $this->driver(DatabaseSearchDriver::name());

        if ($criteria->isFulltext() || ! $defaultDriver->supports($resourceClass)) {
            return $driver->searcher($resourceClass)->search($criteria);
        }

        return $defaultDriver->searcher($resourceClass)->search($criteria);
    }

    /**
     * The filters registered on the database driver's searcher for a model, keyed by filter key.
     *
     * @param class-string<AbstractModel> $modelClass
     * @return array<string, array<\Flarum\Search\Filter\FilterInterface>>
     */
    public function filters(string $modelClass): array
    {
        $searcher = $this->databaseSearcher($modelClass);

        return $searcher ? $searcher->getFilterManager()->all() : [];
    }

    /**
     * Descriptions of the filters registered for a model, keyed by filter key,
     * as seen by the given actor.
     *
     * A description registered through `Extend\SearchDriver::describeFilter()`
     * wins; otherwise the first filter for the key implementing `DescribesFilter`
     * describes itself. Filters with neither are left out, and so are filters
     * whose description is null for this actor.
     *
     * @param class-string<AbstractModel> $modelClass
     * @return array<string, array<string, mixed>>
     */
    public function describeFilters(string $modelClass, User $actor): array
    {
        $searcher = $this->databaseSearcher($modelClass);

        return $searcher ? $this->describeSearcherFilters($searcher, $actor) : [];
    }

    /**
     * @param class-string<AbstractModel> $modelClass
     */
    public function fulltext(string $modelClass): bool
    {
        $searcher = $this->databaseSearcher($modelClass);

        return $searcher && $searcher->getFilterManager()->getFulltext() !== null;
    }

    /**
     * `fulltext()` and `describeFilters()` together, resolving the searcher once.
     *
     * @param class-string<AbstractModel> $modelClass
     * @return array{fulltext: bool, filters: array<string, array<string, mixed>>}
     */
    public function describeSearch(string $modelClass, User $actor): array
    {
        $searcher = $this->databaseSearcher($modelClass);

        if (! $searcher) {
            return ['fulltext' => false, 'filters' => []];
        }

        return [
            'fulltext' => $searcher->getFilterManager()->getFulltext() !== null,
            'filters' => $this->describeSearcherFilters($searcher, $actor),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function describeSearcherFilters(Database\AbstractSearcher $searcher, User $actor): array
    {
        $registered = $this->container->make('flarum.search.filter_descriptions')[$searcher::class] ?? [];

        $described = [];

        foreach ($searcher->getFilterManager()->all() as $key => $filters) {
            $key = (string) $key;
            $description = null;

            if (isset($registered[$key])) {
                $description = $registered[$key];

                // Arrays are static; callables decide per actor and may return null to hide.
                if (! is_array($description)) {
                    $description = ContainerUtil::wrapCallback($description, $this->container)($actor);
                }
            } else {
                foreach ($filters as $filter) {
                    if ($filter instanceof DescribesFilter) {
                        $description = $filter->describe($actor);

                        break;
                    }
                }
            }

            if ($description !== null) {
                $described[$key] = (array) $description;
            }
        }

        return $described;
    }

    protected function databaseSearcher(string $modelClass): ?Database\AbstractSearcher
    {
        $driver = $this->driver(DatabaseSearchDriver::name());

        if (! $driver->supports($modelClass)) {
            return null;
        }

        $searcher = $driver->searcher($modelClass);

        return $searcher instanceof Database\AbstractSearcher ? $searcher : null;
    }

    public function searchable(string $resourceClass): bool
    {
        $driver = $this->driverFor($resourceClass);
        $defaultDriver = $this->driver(DatabaseSearchDriver::name());

        return $driver->supports($resourceClass) || $defaultDriver->supports($resourceClass);
    }
}
