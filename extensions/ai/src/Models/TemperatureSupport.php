<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Models;

use Aws\Exception\AwsException;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\RequestException;
use Throwable;

/**
 * The models that refuse a temperature, as current Claude models do, learnt
 * from the first call that sent one. A feature's temperature is then left out
 * of its calls to them. Kept in the cache: if it's cleared, the next such
 * call learns it again, at the cost of one refused request.
 *
 * @internal
 */
class TemperatureSupport
{
    protected const CACHE_KEY = 'flarum-ai.models.no_temperature';

    public function __construct(
        protected Cache $cache
    ) {
    }

    public function refused(string $connection, string $model): bool
    {
        return in_array(['connection' => $connection, 'model' => $model], $this->all(), true);
    }

    public function remember(string $connection, string $model): void
    {
        if (! $this->refused($connection, $model)) {
            $this->cache->forever(self::CACHE_KEY, [...$this->all(), ['connection' => $connection, 'model' => $model]]);
        }
    }

    /**
     * @return list<array{connection: string, model: string}>
     */
    public function all(): array
    {
        $models = $this->cache->get(self::CACHE_KEY);

        return is_array($models) ? array_values(array_filter($models, fn ($entry) => is_array($entry) && isset($entry['connection'], $entry['model']))) : [];
    }

    /**
     * Whether a provider refused a call for its temperature: a 400 that says
     * so. No provider documents its wording, so any 400 naming it counts;
     * the caller only asks for a temperature every such provider would take.
     */
    public static function isRefusal(Throwable $e): bool
    {
        for (; $e !== null; $e = $e->getPrevious()) {
            if ($e instanceof RequestException) {
                return $e->response->status() === 400 && str_contains(strtolower($e->response->body()), 'temperature');
            }

            if ($e instanceof AwsException) {
                return $e->getAwsErrorCode() === 'ValidationException' && str_contains(strtolower($e->getAwsErrorMessage() ?? $e->getMessage()), 'temperature');
            }
        }

        return false;
    }
}
