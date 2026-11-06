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
use Illuminate\Http\Client\RequestException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Keeps track of the models features use that are deprecated or gone, so the
 * admin can choose others before, or as soon as, calls start failing:
 *
 * - deprecated: the provider still offers it, but has said it's going away;
 * - missing: the provider no longer lists it;
 * - failed: a call to it was answered with a 404.
 *
 * The daily check ({@see check()}) asks each connection in use for its models.
 * A call that finds its model gone records it straight away. Stored in the
 * `flarum-ai.model_issues` setting, only ever written here.
 *
 * @internal
 */
class ModelHealth
{
    public const SETTING = 'flarum-ai.model_issues';

    public function __construct(
        protected FeatureRegistry $features,
        protected FeatureSettings $featureSettings,
        protected AiConfig $config,
        protected ModelCatalog $catalog,
        protected SettingsRepositoryInterface $settings,
        protected LoggerInterface $log
    ) {
    }

    /**
     * The models the features that are on use, by connection, each with the
     * features that use it. A feature without a model of its own uses the
     * connection's default.
     *
     * @return array<string, array<string, string[]>>
     */
    public function inUse(): array
    {
        $connections = $this->config->connections();
        $inUse = [];

        foreach (array_keys($this->features->all()) as $key) {
            $settings = $this->featureSettings->get($key);

            if (! $settings['enabled']) {
                continue;
            }

            $connection = $settings['connection'] !== null && isset($connections[$settings['connection']])
                ? $settings['connection']
                : $this->config->defaultConnection();

            $model = $connection === null ? null : ($settings['model'] ?? $this->catalog->defaultModel($connection));

            if ($model !== null) {
                $inUse[$connection][$model][] = $key;
            }
        }

        return $inUse;
    }

    /**
     * Asks each connection in use for its models, and records which of the
     * models in use are deprecated or missing. A connection that can't be
     * asked keeps what was found before.
     *
     * @return list<array{connection: string, model: string, status: string, since: string, retiresAt: string|null, features: string[]}>
     */
    public function check(): array
    {
        $issues = $this->stored();
        $inUse = $this->inUse();

        foreach ($inUse as $connection => $models) {
            try {
                $listed = $this->catalog->get($connection, true)['models'];
            } catch (ModelsUnavailableException $e) {
                $this->log->warning("[flarum/ai] Could not check the models of the AI connection \"$connection\": {$e->getPrevious()?->getMessage()}");

                continue;
            }

            if ($listed === null) {
                continue;
            }

            $offered = [];

            foreach ($listed as $model) {
                $offered[$model->id] = $model;
            }

            foreach (array_keys($models) as $model) {
                $model = (string) $model;

                if (! isset($offered[$model])) {
                    $issues[$connection][$model] = $this->issue($issues[$connection][$model] ?? null, 'missing');
                } elseif ($offered[$model]->isDeprecated()) {
                    $issues[$connection][$model] = $this->issue($issues[$connection][$model] ?? null, 'deprecated', $offered[$model]->retiresAt);
                } else {
                    unset($issues[$connection][$model]);
                }
            }
        }

        $this->store($this->onlyInUse($issues, $inUse));

        return $this->forAdmin();
    }

    /**
     * Records that a call to a model was answered with a 404.
     */
    public function recordFailure(string $connection, string $model): void
    {
        $issues = $this->stored();

        if (($issues[$connection][$model]['status'] ?? null) === 'failed') {
            return;
        }

        $this->log->warning("[flarum/ai] The AI connection \"$connection\" no longer has the model \"$model\". Choose another one in the AI settings.");

        $issues[$connection][$model] = $this->issue($issues[$connection][$model] ?? null, 'failed');

        $this->store($issues);
    }

    /**
     * Forgets the models no feature uses any more, once the admin has chosen others.
     */
    public function prune(): void
    {
        $issues = $this->stored();
        $pruned = $this->onlyInUse($issues, $this->inUse());

        if ($pruned !== $issues) {
            $this->store($pruned);
        }
    }

    /**
     * @return list<array{connection: string, model: string, status: string, since: string, retiresAt: string|null, features: string[]}>
     */
    public function forAdmin(): array
    {
        $issues = $this->stored();
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
     * Whether a provider's answer to a call means the model is gone.
     */
    public static function isMissingModel(Throwable $e): bool
    {
        for (; $e !== null; $e = $e->getPrevious()) {
            if ($e instanceof RequestException && $e->response->status() === 404) {
                return true;
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
     * @return array<string, array<string, array{status: string, since: string, retiresAt: string|null}>>
     */
    protected function stored(): array
    {
        $issues = json_decode((string) $this->settings->get(self::SETTING), true);

        if (! is_array($issues)) {
            return [];
        }

        // Model IDs that look like numbers come back as integer keys.
        return array_map(
            fn ($models) => is_array($models) ? array_filter($models, fn ($issue) => is_array($issue) && is_string($issue['status'] ?? null) && is_string($issue['since'] ?? null)) : [],
            $issues
        );
    }

    /**
     * @param array<string, array<string, array<string, mixed>>> $issues
     */
    protected function store(array $issues): void
    {
        $this->settings->set(self::SETTING, json_encode((object) array_map(fn ($models) => (object) $models, $issues), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }
}
