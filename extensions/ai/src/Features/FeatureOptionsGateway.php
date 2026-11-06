<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Features;

use Generator;
use Laravel\Ai\Contracts\Gateway\StepTextGateway;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Gateway\StepContext;
use Laravel\Ai\Gateway\StepResponse;
use Laravel\Ai\Gateway\TextGenerationOptions;

/**
 * A provider's text gateway, with the temperature and longest reply the admin
 * set for a feature in place of the agent's own. laravel/ai only reads them
 * from the agent's class, which belongs to the extension, so they're applied
 * here, to each step, on their way to the provider.
 *
 * @internal
 */
class FeatureOptionsGateway implements StepTextGateway
{
    public function __construct(
        protected StepTextGateway $gateway,
        protected ?float $temperature,
        protected ?int $maxTokens
    ) {
    }

    public function generateTextStep(
        TextProvider $provider,
        string $model,
        ?string $instructions,
        array $messages,
        array $tools,
        ?array $schema,
        ?TextGenerationOptions $options,
        ?int $timeout,
        StepContext $stepContext,
    ): StepResponse {
        return $this->gateway->generateTextStep($provider, $model, $instructions, $messages, $tools, $schema, $this->options($options), $timeout, $stepContext);
    }

    public function generateStreamStep(
        string $invocationId,
        TextProvider $provider,
        string $model,
        ?string $instructions,
        array $messages,
        array $tools,
        ?array $schema,
        ?TextGenerationOptions $options,
        ?int $timeout,
        StepContext $stepContext,
    ): Generator {
        return yield from $this->gateway->generateStreamStep($invocationId, $provider, $model, $instructions, $messages, $tools, $schema, $this->options($options), $timeout, $stepContext);
    }

    protected function options(?TextGenerationOptions $options): TextGenerationOptions
    {
        $values = $options ? get_object_vars($options) : [];

        return new TextGenerationOptions(...[
            ...$values,
            'temperature' => $this->temperature ?? $values['temperature'] ?? null,
            'maxTokens' => $this->maxTokens ?? $values['maxTokens'] ?? null,
        ]);
    }
}
