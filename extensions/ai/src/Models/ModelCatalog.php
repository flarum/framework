<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Models;

use Carbon\CarbonImmutable;
use Flarum\Ai\AiConfig;
use Flarum\Ai\Exception\ModelsUnavailableException;
use Illuminate\Contracts\Cache\Repository as Cache;
use InvalidArgumentException;
use Laravel\Ai\AiManager;
use Laravel\Ai\Providers\Provider;
use Throwable;

/**
 * The models each connection offers, asked of its provider and kept for a
 * day. A connection whose provider, address, region or credentials change is
 * asked again.
 *
 * @internal
 */
class ModelCatalog
{
    public const CACHE_SECONDS = 86400;

    public function __construct(
        protected AiConfig $config,
        protected AiManager $manager,
        protected ModelLister $lister,
        protected Cache $cache
    ) {
    }

    /**
     * @return array{models: list<ProviderModel>|null, checkedAt: string} Null models when the provider can't be asked.
     *
     * @throws InvalidArgumentException when there's no such connection
     * @throws ModelsUnavailableException when the provider couldn't be asked
     */
    public function get(string $connection, bool $refresh = false): array
    {
        $driver = $this->driver($connection);

        if (! $this->lister->canList($driver)) {
            return ['models' => null, 'checkedAt' => CarbonImmutable::now('UTC')->toIso8601String()];
        }

        try {
            $provider = $this->provider($connection);
        } catch (Throwable $e) {
            throw new ModelsUnavailableException($connection, $e);
        }

        $cacheKey = $this->cacheKey($connection, $provider);

        if (! $refresh && is_array($cached = $this->cache->get($cacheKey))) {
            return ['models' => array_map(ProviderModel::fromArray(...), $cached['models']), 'checkedAt' => $cached['checkedAt']];
        }

        try {
            $models = $this->lister->list($driver, $provider);
        } catch (Throwable $e) {
            throw new ModelsUnavailableException($connection, $e);
        }

        $result = ['models' => $models, 'checkedAt' => CarbonImmutable::now('UTC')->toIso8601String()];

        $this->cache->put($cacheKey, [
            'models' => array_map(fn (ProviderModel $model) => $model->jsonSerialize(), $models),
            'checkedAt' => $result['checkedAt'],
        ], self::CACHE_SECONDS);

        return $result;
    }

    /**
     * Whether the connection's provider can be asked for its models.
     */
    public function canList(string $connection): bool
    {
        return $this->lister->canList($this->driver($connection));
    }

    /**
     * The model a call through the connection uses when it names none: the
     * connection's own default, or the provider's.
     */
    public function defaultModel(string $connection): ?string
    {
        $this->driver($connection);

        try {
            return $this->manager->textProvider($connection)->defaultTextModel();
        } catch (Throwable) {
            return null;
        }
    }

    protected function driver(string $connection): string
    {
        $config = $this->config->connections()[$connection] ?? throw new InvalidArgumentException("No AI connection called \"$connection\".");

        return $config['driver'];
    }

    protected function provider(string $connection): Provider
    {
        /** @var Provider */
        return $this->manager->instance($connection);
    }

    /**
     * Changes with whatever the list depends on: the provider, its address or
     * region and the rest of its settings, and its credentials, which are
     * hashed so they're never stored.
     */
    protected function cacheKey(string $connection, Provider $provider): string
    {
        return 'flarum-ai.models.'.hash('sha256', json_encode([
            $connection,
            $provider->driver(),
            $provider->additionalConfiguration(),
            hash('sha256', (string) json_encode($provider->providerCredentials())),
        ]));
    }
}
