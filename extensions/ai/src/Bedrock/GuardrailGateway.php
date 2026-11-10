<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Bedrock;

use Generator;
use Laravel\Ai\Contracts\Gateway\StepTextGateway;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Gateway\StepContext;
use Laravel\Ai\Gateway\StepResponse;
use Laravel\Ai\Gateway\TextGenerationOptions;

/**
 * A Bedrock connection's text gateway, with the guardrail the admin set up for
 * the connection on every reply an agent generates through it. laravel/ai
 * passes Bedrock's own options straight into the Converse request, so the
 * guardrail goes there, in place of any the agent asks for: it's the forum's
 * policy, not the agent's. Embeddings, images and reranking aren't guarded:
 * Bedrock applies guardrails to Converse calls.
 *
 * {@see \Flarum\Ai\ContentFilter} tells whether it stepped in. Why it did is
 * in Bedrock's trace, which laravel/ai doesn't keep, so it isn't asked for.
 *
 * @internal
 */
class GuardrailGateway implements StepTextGateway
{
    public function __construct(
        protected StepTextGateway $gateway,
        protected string $guardrail,
        protected string $version
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
            'providerOptions' => [
                ...($values['providerOptions'] ?? []),
                'guardrailConfig' => [
                    'guardrailIdentifier' => $this->guardrail,
                    'guardrailVersion' => $this->version,
                ],
            ],
        ]);
    }
}
