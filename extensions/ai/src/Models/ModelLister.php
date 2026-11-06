<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Models;

use Carbon\CarbonImmutable;
use Closure;
use Flarum\Ai\Drivers;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Providers\Provider;
use Throwable;
use UnexpectedValueException;

/**
 * Asks a provider's API which models it offers, with the connection's own
 * address and key. Every provider the admin page can set up can be asked;
 * Azure, which only config.php can set up, can't.
 *
 * @internal
 */
class ModelLister
{
    /**
     * Each provider's own address, as laravel/ai has it, for connections that
     * don't give one.
     */
    public const DEFAULT_URLS = [
        'anthropic' => 'https://api.anthropic.com/v1',
        'deepseek' => 'https://api.deepseek.com/v1',
        'gemini' => 'https://generativelanguage.googleapis.com/v1beta',
        'groq' => 'https://api.groq.com/openai/v1',
        'mistral' => 'https://api.mistral.ai/v1',
        'ollama' => 'http://localhost:11434',
        'openai' => 'https://api.openai.com/v1',
        'openrouter' => 'https://openrouter.ai/api/v1',
        'xai' => 'https://api.x.ai/v1',
    ];

    /**
     * Providers whose list follows OpenAI's: `data`, each with an `id`.
     */
    protected const OPENAI_SHAPED = ['deepseek', 'groq', 'mistral', 'openai', 'openai-compatible', 'xai'];

    /**
     * Enough for any provider's list, and few enough to stop a runaway one.
     */
    protected const MAX_PAGES = 20;

    public function __construct(
        /** Sends Bedrock's requests in place of the network, in tests. */
        protected ?Closure $awsHandler = null
    ) {
    }

    public function canList(string $driver): bool
    {
        return isset(self::DEFAULT_URLS[$driver])
            || in_array($driver, self::OPENAI_SHAPED, true)
            || ($driver === 'bedrock' && Drivers::awsSdkInstalled());
    }

    /**
     * @return list<ProviderModel> Sorted by ID.
     *
     * @throws Throwable when the provider can't be reached or doesn't answer with a list
     */
    public function list(string $driver, Provider $provider): array
    {
        if ($driver === 'bedrock') {
            $models = (new BedrockModels($this->awsHandler))->list($provider);
            usort($models, fn (ProviderModel $a, ProviderModel $b) => strcmp($a->id, $b->id));

            return $models;
        }

        $config = $provider->additionalConfiguration();
        $key = $provider->providerCredentials()['key'] ?? null;
        $url = rtrim(is_string($config['url'] ?? null) && $config['url'] !== '' ? $config['url'] : (self::DEFAULT_URLS[$driver] ?? ''), '/');
        $http = Http::timeout(15)->acceptJson()->withHeaders(is_array($config['headers'] ?? null) ? $config['headers'] : []);

        if ($url === '') {
            throw new UnexpectedValueException("The $driver provider needs an API URL to list its models.");
        }

        $models = match ($driver) {
            'anthropic' => $this->anthropic($http, $url, $key, $config),
            'gemini' => $this->gemini($http, $url, $key),
            'ollama' => $this->ollama($http, $url, $key),
            'openrouter' => $this->openRouter($http, $url, $key),
            default => $this->openAiShaped($http, $url, $key),
        };

        usort($models, fn (ProviderModel $a, ProviderModel $b) => strcmp($a->id, $b->id));

        return $models;
    }

    /**
     * @return list<ProviderModel>
     */
    protected function openAiShaped(PendingRequest $http, string $url, ?string $key): array
    {
        $data = $this->bearer($http, $key)->get("$url/models")->throw()->json('data');

        return array_map(fn (array $model) => new ProviderModel(
            $model['id'],
            null,
            null,
            // OpenAI and Mistral say when they retire a model, each its own way.
            $this->date($model['shutdown_date'] ?? $model['deprecation'] ?? null)
        ), $this->items($data, 'id'));
    }

    /**
     * @return list<ProviderModel>
     */
    protected function openRouter(PendingRequest $http, string $url, ?string $key): array
    {
        $data = $this->bearer($http, $key)->get("$url/models")->throw()->json('data');

        return array_map(fn (array $model) => new ProviderModel(
            $model['id'],
            is_string($model['name'] ?? null) ? $model['name'] : null,
            null,
            $this->date($model['expiration_date'] ?? null)
        ), $this->items($data, 'id'));
    }

    /**
     * @param array<string, mixed> $config
     * @return list<ProviderModel>
     */
    protected function anthropic(PendingRequest $http, string $url, ?string $key, array $config): array
    {
        $http = $http->withHeaders(array_filter([
            'x-api-key' => $key,
            'anthropic-version' => $config['version'] ?? '2023-06-01',
        ]));

        $models = [];
        $after = null;

        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $response = $http->get("$url/models", array_filter(['limit' => 1000, 'after_id' => $after]))->throw();

            foreach ($this->items($response->json('data'), 'id') as $model) {
                $models[] = new ProviderModel(
                    $model['id'],
                    is_string($model['display_name'] ?? null) ? $model['display_name'] : null,
                    $this->date($model['deprecated_at'] ?? null),
                    $this->date($model['retires_at'] ?? null)
                );
            }

            $after = $response->json('last_id');

            if ($response->json('has_more') !== true || ! is_string($after)) {
                break;
            }
        }

        return $models;
    }

    /**
     * Only models that generate text: Gemini lists its embedding models too.
     *
     * @return list<ProviderModel>
     */
    protected function gemini(PendingRequest $http, string $url, ?string $key): array
    {
        $http = $http->withHeaders(array_filter(['x-goog-api-key' => $key]));

        $models = [];
        $token = null;

        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $response = $http->get("$url/models", array_filter(['pageSize' => 1000, 'pageToken' => $token]))->throw();

            foreach ($this->items($response->json('models'), 'name') as $model) {
                if (! in_array('generateContent', (array) ($model['supportedGenerationMethods'] ?? []), true)) {
                    continue;
                }

                $models[] = new ProviderModel(
                    preg_replace('#^models/#', '', $model['name']),
                    is_string($model['displayName'] ?? null) ? $model['displayName'] : null
                );
            }

            $token = $response->json('nextPageToken');

            if (! is_string($token) || $token === '') {
                break;
            }
        }

        return $models;
    }

    /**
     * The models pulled to the Ollama server.
     *
     * @return list<ProviderModel>
     */
    protected function ollama(PendingRequest $http, string $url, ?string $key): array
    {
        $data = $this->bearer($http, $key)->get("$url/api/tags")->throw()->json('models');

        return array_map(fn (array $model) => new ProviderModel($model['name']), $this->items($data, 'name'));
    }

    protected function bearer(PendingRequest $http, ?string $key): PendingRequest
    {
        return $key !== null && $key !== '' ? $http->withToken($key) : $http;
    }

    /**
     * The entries of a list that name a model, refusing an answer that isn't a list.
     *
     * @return list<array<string, mixed>>
     */
    protected function items(mixed $data, string $idField): array
    {
        if (! is_array($data) || ! array_is_list($data)) {
            throw new UnexpectedValueException('The provider did not answer with a list of models.');
        }

        return array_values(array_filter(
            $data,
            fn ($item) => is_array($item) && is_string($item[$idField] ?? null) && $item[$idField] !== ''
        ));
    }

    protected function date(mixed $value): ?string
    {
        if (is_int($value) && $value > 0) {
            return CarbonImmutable::createFromTimestampUTC($value)->toIso8601String();
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value, 'UTC')->utc()->toIso8601String();
        } catch (Throwable) {
            return null;
        }
    }
}
