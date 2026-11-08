<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Models;

use Aws\Exception\AwsException;
use Carbon\CarbonImmutable;
use Flarum\Ai\AiConfig;
use Flarum\Ai\Exception\ModelsUnavailableException;
use Flarum\Ai\Features\FeatureRegistry;
use Flarum\Ai\Features\FeatureSettings;
use Flarum\Settings\SettingsRepositoryInterface;
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
 * one is only known once the feature has made a call, so each such call
 * notes the model it used ({@see observe()}), and under which default. A
 * model seen under another default than the connection's now isn't trusted.
 *
 * @internal
 */
class ModelHealth
{
    public const SETTING = 'flarum-ai.model_issues';

    /**
     * The model each feature without one of its own was last seen using,
     * kept as a setting so a cleared cache doesn't lose it. Only ever
     * written here.
     */
    public const OBSERVED_SETTING = 'flarum-ai.observed_models';

    public function __construct(
        protected FeatureRegistry $features,
        protected FeatureSettings $featureSettings,
        protected AiConfig $config,
        protected ModelCatalog $catalog,
        protected SettingsRepositoryInterface $settings,
        protected ConnectionInterface $db,
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

            $default = $this->catalog->defaultModel($connection);
            $seen = $observed[$key] ?? null;

            $model = $settings['model']
                ?? ($seen !== null && $seen['connection'] === $connection && ($seen['default'] ?? null) === $default ? $seen['model'] : null)
                ?? $default;

            if ($model !== null) {
                $inUse[$connection][$model][] = $key;
            }
        }

        return $inUse;
    }

    /**
     * Notes the model a call for a feature without a model of its own used,
     * and the connection's default at the time.
     */
    public function observe(string $feature, string $connection, string $model, ?string $default): void
    {
        $entry = ['connection' => $connection, 'model' => $model, 'default' => $default];

        if (($this->observed()[$feature] ?? null) === $entry) {
            return;
        }

        $this->update(self::OBSERVED_SETTING, function (array $observed) use ($feature, $entry) {
            $observed[$feature] = $entry;

            return $observed;
        });
    }

    /**
     * Asks each connection in use for its models, and records which of the
     * models in use are deprecated or missing. A connection that can't be
     * asked keeps what was found before, and is listed as unchecked.
     *
     * Connections whose providers can't list their models, such as Azure,
     * are listed as unlisted.
     *
     * @return array{issues: list<array{connection: string, model: string, status: string, since: string, retiresAt: string|null, features: string[]}>, unchecked: string[], unlisted: string[]}
     */
    public function check(): array
    {
        $startedAt = CarbonImmutable::now('UTC');
        $inUse = $this->inUse();
        $found = [];
        $unchecked = [];
        $unlisted = [];

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
                $unlisted[] = $connection;

                continue;
            }

            foreach (array_keys($models) as $model) {
                if (! self::checkable((string) $model)) {
                    continue;
                }

                $match = self::match($listed, (string) $model);

                $found[$connection][(string) $model] = match (true) {
                    $match === null => ['missing', null],
                    $match->isDeprecated() => ['deprecated', $match->retiresAt],
                    default => null,
                };
            }
        }

        // Applied to what's stored now, not what was read before asking the
        // providers, so a failure recorded meanwhile is kept: one for a model
        // checked here too, since the call that found it is newer news.
        $this->update(self::SETTING, function (array $issues) use ($found, $inUse, $startedAt) {
            foreach ($found as $connection => $models) {
                foreach ($models as $model => $result) {
                    $current = $issues[$connection][$model] ?? null;

                    if (($current['status'] ?? null) === 'failed' && CarbonImmutable::parse($current['since'])->gte($startedAt)) {
                        continue;
                    }

                    if ($result === null) {
                        unset($issues[$connection][$model]);
                    } else {
                        $issues[$connection][$model] = $this->issue($current, ...$result);
                    }
                }
            }

            return $this->onlyInUse($issues, $inUse);
        });

        return ['issues' => $this->forAdmin(), 'unchecked' => $unchecked, 'unlisted' => $unlisted];
    }

    /**
     * Records that a call to a model was answered with a 404 that named it.
     */
    public function recordFailure(string $connection, string $model): void
    {
        // Every call to a model that's gone fails: only the first needs the lock.
        if (($this->decode($this->settings->get(self::SETTING))[$connection][$model]['status'] ?? null) === 'failed') {
            return;
        }

        $this->update(self::SETTING, function (array $issues) use ($connection, $model) {
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

        $this->update(self::SETTING, fn (array $issues) => $this->onlyInUse($issues, $inUse));
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
     *   `claude-x-20261001`;
     * - a Bedrock model's or inference profile's ARN.
     *
     * Of several releases an alias could mean, one still offered wins.
     *
     * @param list<ProviderModel> $listed
     */
    public static function match(array $listed, string $model): ?ProviderModel
    {
        // Bedrock takes a model's or inference profile's ARN as well as its ID.
        if (preg_match('#^arn:aws[a-z-]*:bedrock:[^:]*:[^:]*:(?:foundation-model|inference-profile)/(.+)$#', $model, $arn)) {
            $model = $arn[1];
        }

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
     * Whether a provider's answer to a call means the model is gone:
     *
     * - over HTTP, a 404 that names it, as a whole name, not part of a longer
     *   one. A 404 that doesn't, such as from a wrong API URL, isn't;
     * - from Bedrock, a ResourceNotFoundException that names the model, or one
     *   or a ValidationException that calls its identifier invalid or says it
     *   reached the end of its life. Bedrock uses the same errors for
     *   other things, such as a guardrail that doesn't exist, and doesn't
     *   document what a retired model's says, so a retired model it words
     *   otherwise is left to the daily check, which reads Bedrock's lifecycle.
     */
    public static function isMissingModel(Throwable $e, string $model): bool
    {
        for (; $e !== null; $e = $e->getPrevious()) {
            if ($e instanceof RequestException && $e->response->status() === 404) {
                return self::names($e->response->body(), $model);
            }

            if ($e instanceof AwsException) {
                $message = $e->getAwsErrorMessage() ?? $e->getMessage();

                return match ($e->getAwsErrorCode()) {
                    'ResourceNotFoundException' => self::names($message, $model) || (bool) preg_match('/model identifier|end of (its )?life/i', $message),
                    // Also used for a model called the wrong way, such as
                    // without the inference profile it needs, which names it.
                    'ValidationException' => (bool) preg_match('/model identifier|end of (its )?life/i', $message),
                    default => false,
                };
            }
        }

        return false;
    }

    /**
     * Whether a model can be checked against its provider's list. Bedrock
     * takes ARNs for things it doesn't list: application inference profiles,
     * provisioned throughput and custom or imported models.
     */
    public static function checkable(string $model): bool
    {
        return ! str_starts_with($model, 'arn:')
            || (bool) preg_match('#^arn:aws[a-z-]*:bedrock:[^:]*:[^:]*:(?:foundation-model|inference-profile)/#', $model);
    }

    /**
     * Whether a message names a model, as a whole name, not part of a longer one.
     */
    protected static function names(string $message, string $model): bool
    {
        return (bool) preg_match('/(?<![A-Za-z0-9._:-])'.preg_quote($model, '/').'(?![A-Za-z0-9._:\/-])/', $message);
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
     * Changes a setting as it is in the database now, not as this request
     * first read it: calls in other requests write it too. The row is made
     * first, so there's always one to lock.
     *
     * @param callable(array<string, mixed>): array<string, mixed> $change
     */
    protected function update(string $key, callable $change): void
    {
        $this->db->table('settings')->insertOrIgnore(['key' => $key, 'value' => '{}']);

        $this->db->transaction(function () use ($key, $change) {
            $stored = $this->db->table('settings')->where('key', $key)->lockForUpdate()->value('value');
            $current = json_decode((string) $stored, true);
            $current = $key === self::SETTING ? $this->decode($stored) : (is_array($current) ? $current : []);
            $changed = $change($current);

            if ($changed !== $current || $stored !== $this->settings->get($key)) {
                // Through the repository, so this request reads it afterwards too.
                $this->settings->set($key, json_encode(
                    $key === self::SETTING ? (object) array_map(fn ($models) => (object) $models, $changed) : (object) $changed,
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
     * @return array<string, array{connection: string, model: string, default: string|null}>
     */
    protected function observed(): array
    {
        $observed = json_decode((string) $this->settings->get(self::OBSERVED_SETTING), true);

        return is_array($observed) ? array_filter(
            $observed,
            fn ($entry) => is_array($entry) && is_string($entry['connection'] ?? null) && is_string($entry['model'] ?? null)
        ) : [];
    }
}
