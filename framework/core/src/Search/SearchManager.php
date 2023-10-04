<?php

namespace Flarum\Search;

use Flarum\Database\AbstractModel;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use InvalidArgumentException;

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

    public function driver(string $name): AbstractDriver
    {
        $driver = Arr::first($this->drivers, fn ($driver) => $driver::name() === $name);

        if (! $driver) {
            $driver = $this->driver(DatabaseSearchDriver::name());
        }

        return $this->container->make($driver);
    }

    public function driverFor(string $resourceClass): AbstractDriver
    {
        return $this->driver($this->settings->get("search_driver_$resourceClass"));
    }

    public function searchable(string $resourceClass): bool
    {
        return $this->driverFor($resourceClass)->supports($resourceClass);
    }

    /**
     * @param class-string<AbstractModel> $resourceClass
     */
    public function for(string $resourceClass): SearcherInterface
    {
        $driver = $this->driverFor($resourceClass);
        $searchers = $driver->searchers();

        if (! isset($searchers[$resourceClass])) {
            throw new InvalidArgumentException("Driver {$driver::name()} does not support searching for $resourceClass.");
        }

        return $this->container->make($searchers[$resourceClass]);
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
}
