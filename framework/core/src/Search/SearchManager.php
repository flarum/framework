<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

use Flarum\Database\AbstractModel;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\Settings\SettingsRepositoryInterface;
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

    public function searchable(string $resourceClass): bool
    {
        $driver = $this->driverFor($resourceClass);
        $defaultDriver = $this->driver(DatabaseSearchDriver::name());

        return $driver->supports($resourceClass) || $defaultDriver->supports($resourceClass);
    }
}
