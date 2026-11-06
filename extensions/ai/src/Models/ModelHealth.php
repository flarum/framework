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
use Flarum\Ai\Features\FeatureRegistry;
use Flarum\Ai\Features\FeatureSettings;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Client\RequestException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Keeps track of the models features use that are deprecated or gone, so the
 * admin can choose others before, or as soon as, calls start failing:
 *
 * - deprecated: the provider still offers it, but has said it's going away;
 * - missing: the provider no longer lists it;
 * - failed: a call to it was answered with a 404 that named it.
 *
 * The daily check ({@see check()}) asks each connection in use for its models.
 * A call that finds its model gone records it straight away. Stored in the
 * `flarum-ai.model_issues` setting, only ever written here.
 *
 * A feature uses the model the admin chose for it. Without one, it uses the
 * model its agent chooses, if it does, or the connection's default: which
 * one is only known once the feature has made a call, so each feature call
 * notes the model it used ({@see observe()}).
 *
 * @internal
 */
class ModelHealth
{
    public const SETTING = 'flarum-ai.model_issues';

    protected const OBSERVED_CACHE_KEY = 'flarum-ai.models.observed';

    public function __construct(
        protected FeatureRegistry $features,
        protected FeatureSettings $featureSettings,
        protected AiConfig $config,
        protected ModelCatalog $catalog,
        protected SettingsRepositoryInterface $settings,
        protected ConnectionInterface $db,
        protected Cache $cache,
        protected LoggerInterface $log
    ) {
    }

    /**
     * The models the features that are on use, by connection, each with the
     * features that use it.
     *
     * @return array<string, array<string, string[]>>
     */
    public function inUse(): array
    {
        $connections = $this->config->connections();
        $observed = $this->observed();
        $inUse = [];

        foreach (array_keys($this->features->all()) as $key) {
            $settings = $this->featureSettings->get($key);

            if (! $settings['enabled']) {
                continue;
            }

            $connection = $settings['connection'] !== null && isset($connections[$settings['connection']])
                ? $settings['connection']
                : $this->config->defaultConnection();

            if ($connection === null) {
                continue;
            }

            $model = $settings['model']
                ?? (($observed[$key]['connection'] ?? null) === $connection ? $observed[$key]['model'] : null)
                ?? $this->catalog->defaultModel($connection);

            if ($model !== null) {
                $inUse[$connection][$model][] = $key;
            }
        }

        return $inUse;
    }

    /**
     * Notes the model a feature's call used, when it's not the one last noted.
     */
    public function observe(string $feature, string $connection, string $model): void
    {
        $observed = $this->observed();

        if (($observed[$feature] ?? null) === ['connection' => $connection, 'model' => $model]) {
            return;
        }

        $observed[$feature] = ['connection' => $connection, 'model' => $model];

        $this->cache->forever(self::OBSERVED_CACHE_KEY, $observed);
    }

    /**
     * Asks each connection in use for its models, and records which of the
     * models in use are deprecated or missing. A connection that can't be
     * asked keeps what was found before, and is listed as unchecked.
     *
     * @return array{issues: list<array{connection: string, model: string, status: string, since: string, retiresAt: string|null, features: string[]}>, unchecked: string[]}
     */
    public function check(): array
    {
        $inUse = $this->inUse();
        $found = [];
        $unchecked = [];

        foreach ($inUse as $connection => $models) {
            $connection = (string) $connection;

            try {
                $listed = $this->catalog->get($connection, true)['models'];
            } catch (ModelsUnavailableException $e) {
                $this->log->warning("[flarum/ai] Could not check the models of the AI connection \"$connection\": {$e->getPrevious()?->getMessage()}");
                $unchecked[] = $connection;

                continue;
            }

            if ($listed === null) {
                continue;
            }

            foreach (array_keys($models) as $model) {
                $match = self::match($listed, (string) $model);

                $found[$connection][(string) $model] = match (true) {
                    $match === null => ['missing', null],
                    $match->isDeprecated() => ['deprecated', $match->retiresAt],
                    default => null,
                };
            }
        }

        // Applied to what's stored now, not what was read before asking the
        // providers, so a failure recorded meanwhile is kept.
        $this->update(function (array $issues) use ($found, $inUse) {
            foreach ($found as $connection => $models) {
                foreach ($models as $model => $result) {
                    if ($result === null) {
                        unset($issues[$connection][$model]);
                    } else {
                        $issues[$connection][$model] = $this->issue($issues[$connection][$model] ?? null, ...$result);
                    }
                }
            }

            return $this->onlyInUse($issues, $inUse);
        });

        return ['issues' => $this->forAdmin(), 'unchecked' => $unchecked];
    }

    /**
     * Records that a call to a model was answered with a 404 that named it.
     */
    public function recordFailure(string $connection, string $model): void
    {
        $this->update(function (array $issues) use ($connection, $model) {
            if (($issues[$connection][$model]['status'] ?? null) !== 'failed') {
                $this->log->warning("[flarum/ai] The AI connection \"$connection\" no longer has the model \"$model\". Choose another one in the AI settings.");

                $issues[$connection][$model] = $this->issue($issues[$connection][$model] ?? null, 'failed');
            }

            return $issues;
        });
    }

    /**
     * Forgets the models no feature uses any more, once the admin has chosen others.
     */
    public function prune(): void
    {
        $inUse = $this->inUse();

        $this->update(fn (array $issues) => $this->onlyInUse($issues, $inUse));
    }

    /**
     * @return list<array{connection: string, model: string, status: string, since: string, retiresAt: string|null, features: string[]}>
     */
    public function forAdmin(): array
    {
        $issues = $this->decode($this->settings->get(self::SETTING));
        $inUse = $issues === [] ? [] : $this->inUse();
        $result = [];

        foreach ($issues as $connection => $models) {
            foreach ($models as $model => $issue) {
                $result[] = [
                    'connection' => (string) $connection,
                    'model' => (string) $model,
                    'status' => $issue['status'],
                    'since' => $issue['since'],
                    'retiresAt' => $issue['retiresAt'] ?? null,
                    'features' => $inUse[$connection][$model] ?? [],
                ];
            }
        }

        return $result;
    }

    /**
     * The model a provider's list offers under a name, if it does. A name can
     * be one the provider takes but doesn't list:
     *
     * - Ollama serves `llama3.2` as `llama3.2:latest`;
     * - an alias for a dated release, such as Anthropic's `claude-x` for
     *   `claude-x-20261001`.
     *
     * Of several releases an alias could mean, one still offered wins.
     *
     * @param list<ProviderModel> $listed
     */
    public static function match(array $listed, string $model): ?ProviderModel
    {
        $alias = '/^'.preg_quote($model, '/').'-(\d{8}|\d{4}-\d{2}-\d{2}|\d{3})$/';
        $matches = [];

        foreach ($listed as $offered) {
            if ($offered->id === $model) {
                return $offered;
            }

            if ($offered->id === "$model:latest" || preg_match($alias, $offered->id)) {
                $matches[] = $offered;
            }
        }

        foreach ($matches as $match) {
            if (! $match->isDeprecated()) {
                return $match;
            }
        }

        return $matches[0] ?? null;
    }

    /**
     * Whether a provider's answer to a call means the model is gone: a 404
     * that names it. A 404 that doesn't, such as from a wrong API URL, isn't.
     */
    public static function isMissingModel(Throwable $e, string $model): bool
    {
        for (; $e !== null; $e = $e->getPrevious()) {
            if ($e instanceof RequestException && $e->response->status() === 404) {
                return str_contains($e->response->body(), $model);
            }
        }

        return false;
    }

    /**
     * @param array{status: string, since: string, retiresAt?: string|null}|null $previous
     * @return array{status: string, since: string, retiresAt: string|null}
     */
    protected function issue(?array $previous, string $status, ?string $retiresAt = null): array
    {
        return [
            'status' => $status,
            // Since when it's been in this state.
            'since' => ($previous['status'] ?? null) === $status ? $previous['since'] : CarbonImmutable::now('UTC')->toIso8601String(),
            'retiresAt' => $retiresAt,
        ];
    }

    /**
     * @param array<string, array<string, array<string, mixed>>> $issues
     * @param array<string, array<string, string[]>> $inUse
     * @return array<string, array<string, array<string, mixed>>>
     */
    protected function onlyInUse(array $issues, array $inUse): array
    {
        foreach ($issues as $connection => $models) {
            $issues[$connection] = array_intersect_key($models, $inUse[$connection] ?? []);

            if ($issues[$connection] === []) {
                unset($issues[$connection]);
            }
        }

        return $issues;
    }

    /**
     * Changes the stored issues as they are in the database now, not as this
     * request first read them: calls in other requests record failures too.
     *
     * @param callable(array<string, array<string, array<string, mixed>>>): array<string, array<string, array<string, mixed>>> $change
     */
    protected function update(callable $change): void
    {
        $this->db->transaction(function () use ($change) {
            $stored = $this->db->table('settings')->where('key', self::SETTING)->lockForUpdate()->value('value');
            $issues = $this->decode($stored);
            $changed = $change($issues);

            if ($changed !== $issues) {
                $this->settings->set(self::SETTING, json_encode(
                    (object) array_map(fn ($models) => (object) $models, $changed),
                    JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                ));
            }
        });
    }

    /**
     * @return array<string, array<string, array{status: string, since: string, retiresAt: string|null}>>
     */
    protected function decode(mixed $json): array
    {
        $issues = is_string($json) ? json_decode($json, true) : null;

        if (! is_array($issues)) {
            return [];
        }

        return array_filter(array_map(
            fn ($models) => is_array($models) ? array_filter($models, fn ($issue) => is_array($issue) && is_string($issue['status'] ?? null) && is_string($issue['since'] ?? null)) : [],
            $issues
        ));
    }

    /**
     * @return array<string, array{connection: string, model: string}>
     */
    protected function observed(): array
    {
        $observed = $this->cache->get(self::OBSERVED_CACHE_KEY);

        return is_array($observed) ? $observed : [];
    }
}
