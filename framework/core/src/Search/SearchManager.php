<?php

namespace Flarum\Search;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class SearchManager
{
    public function __construct(
        /** @var array<class-string<AbstractDriver>> */
        protected array $drivers,
        protected SettingsRepositoryInterface $settings,
        protected Container $container
    ) {
    }

    public function driver(string $name): AbstractDriver
    {
        $driver = Arr::first($this->drivers, fn ($driver) => $driver::name() === $name);

        if (! $driver) {
            throw new InvalidArgumentException("Driver `$name` is not defined.");
        }

        return $this->container->make($driver);
    }

    public function for(string $resourceClass): SearcherInterface
    {
        $driver = $this->driver($this->settings->get("search_driver_$resourceClass"));
        $searchers = $driver->searchers();

        if (! isset($searchers[$resourceClass])) {
            throw new InvalidArgumentException("Driver {$driver::name()} does not support searching for $resourceClass.");
        }

        return $this->container->make($searchers[$resourceClass]);
    }
}
