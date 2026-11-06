<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Models;

use Aws\Bedrock\BedrockClient;
use Aws\Middleware;
use Carbon\CarbonImmutable;
use Closure;
use DateTimeInterface;
use Laravel\Ai\Gateway\Bedrock\Concerns\CreatesBedrockClient;
use Laravel\Ai\Providers\Provider;
use Psr\Http\Message\RequestInterface;
use Throwable;

/**
 * The models a Bedrock connection can call: foundation models it can call on
 * demand, and the inference profiles (`global.`, `us.`, `eu.`, ...) that
 * route calls to models across regions, which is how most are called. A
 * profile shares its model's lifecycle: legacy from one date, gone from another.
 *
 * Asked with the connection's own credentials, through laravel/ai's own way
 * of finding them, so it works however the connection authenticates.
 *
 * @internal
 */
class BedrockModels
{
    use CreatesBedrockClient;

    protected const MAX_PAGES = 20;

    public function __construct(
        /** Sends the requests in place of the network, in tests. */
        protected ?Closure $handler = null
    ) {
    }

    /**
     * @return list<ProviderModel>
     */
    public function list(Provider $provider): array
    {
        $client = $this->client($provider);

        $foundation = [];

        foreach ($client->listFoundationModels(['byOutputModality' => 'TEXT'])['modelSummaries'] ?? [] as $summary) {
            if (is_string($summary['modelId'] ?? null)) {
                $foundation[$summary['modelId']] = $summary;
            }
        }

        $models = [];

        foreach ($foundation as $id => $summary) {
            if (in_array('ON_DEMAND', $summary['inferenceTypesSupported'] ?? [], true)) {
                $models[] = $this->model($id, $summary['modelName'] ?? null, $summary);
            }
        }

        $token = null;

        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $result = $client->listInferenceProfiles(array_filter(['typeEquals' => 'SYSTEM_DEFINED', 'maxResults' => 1000, 'nextToken' => $token]));

            foreach ($result['inferenceProfileSummaries'] ?? [] as $profile) {
                if (! is_string($profile['inferenceProfileId'] ?? null) || ($profile['status'] ?? 'ACTIVE') !== 'ACTIVE') {
                    continue;
                }

                $arn = (string) ($profile['models'][0]['modelArn'] ?? '');
                $underlying = $foundation[substr($arn, (int) strrpos($arn, 'foundation-model/') + strlen('foundation-model/'))] ?? [];

                $models[] = $this->model($profile['inferenceProfileId'], $profile['inferenceProfileName'] ?? null, $underlying);
            }

            $token = $result['nextToken'] ?? null;

            if (! is_string($token) || $token === '') {
                break;
            }
        }

        return $models;
    }

    /**
     * @param array<string, mixed> $summary The foundation model's summary, with its lifecycle.
     */
    protected function model(string $id, mixed $name, array $summary): ProviderModel
    {
        $lifecycle = is_array($summary['modelLifecycle'] ?? null) ? $summary['modelLifecycle'] : [];
        $legacy = ($lifecycle['status'] ?? null) === 'LEGACY';

        return new ProviderModel(
            $id,
            is_string($name) ? $name : null,
            // Legacy is Bedrock's word for deprecated, whether or not it says since when.
            $legacy ? $this->date($lifecycle['legacyTime'] ?? null) ?? $this->date($lifecycle['endOfLifeTime'] ?? null) ?? CarbonImmutable::now('UTC')->toIso8601String() : null,
            $this->date($lifecycle['endOfLifeTime'] ?? null)
        );
    }

    protected function date(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)->utc()->toIso8601String();
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

    protected function client(Provider $provider): BedrockClient
    {
        $config = $provider->additionalConfiguration();

        $client = new BedrockClient(array_filter([
            'region' => $this->bedrockRegion($config),
            'version' => '2023-04-20',
            'http' => ['timeout' => 15],
            'handler' => $this->handler,
            ...$this->resolveAuthConfig($provider->providerCredentials(), $config, 15),
        ], fn ($value) => $value !== null));

        // As laravel/ai sends them with every call, for a proxy that needs them.
        if ($headers = $config['headers'] ?? []) {
            $client->getHandlerList()->appendBuild(Middleware::mapRequest(function (RequestInterface $request) use ($headers): RequestInterface {
                foreach ($headers as $name => $value) {
                    $request = $request->withHeader($name, $value);
                }

                return $request;
            }), 'flarum-ai.headers');
        }

        return $client;
    }
}
