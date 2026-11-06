<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Features;

use Closure;
use Flarum\Ai\Models\ModelHealth;
use Generator;
use Laravel\Ai\Contracts\Gateway\StepTextGateway;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Gateway\StepContext;
use Laravel\Ai\Gateway\StepResponse;
use Laravel\Ai\Gateway\TextGenerationOptions;
use Throwable;

/**
 * A provider's text gateway, as a feature uses it:
 *
 * - The temperature and longest reply the admin set for the feature are sent
 *   in place of the agent's own. laravel/ai only reads them from the agent's
 *   class, which belongs to the extension, so they're applied here, to each
 *   step, on their way to the provider.
 * - When the model the admin chose for the feature is gone (the provider
 *   answers with a 404 that names it), the step is made again with the
 *   connection's default model, and the missing model is recorded for the
 *   admin to see. A stream is only made again if it failed before sending
 *   anything.
 * - The model each step uses is noted, so the daily check knows which model
 *   the feature uses when the admin chose none.
 *
 * @internal
 */
class FeatureGateway implements StepTextGateway
{
    /**
     * @param string|null $model The model the admin chose for the feature.
     * @param Closure(): ?string $fallbackModel The connection's default model, if it has one.
     * @param Closure(string $model): void $onMissingModel
     * @param Closure(string $model): void $onStep Told the model of each step of a feature without a model of its own.
     */
    public function __construct(
        protected StepTextGateway $gateway,
        protected ?float $temperature,
        protected ?int $maxTokens,
        protected ?string $model,
        protected Closure $fallbackModel,
        protected Closure $onMissingModel,
        protected Closure $onStep
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
        $options = $this->options($options);

        return $this->withFallback($model, fn (string $model) => $this->gateway->generateTextStep(
            $provider, $model, $instructions, $messages, $tools, $schema, $options, $timeout, $stepContext
        ));
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
        $options = $this->options($options);

        // Started here, so a request that fails fails before anything is sent on.
        $stream = $this->withFallback($model, function (string $model) use ($invocationId, $provider, $instructions, $messages, $tools, $schema, $options, $timeout, $stepContext) {
            $stream = $this->gateway->generateStreamStep($invocationId, $provider, $model, $instructions, $messages, $tools, $schema, $options, $timeout, $stepContext);
            $stream->current();

            return $stream;
        });

        // PHP can't `yield from` a generator that finished while starting.
        if (! $stream->valid()) {
            return $stream->getReturn();
        }

        return yield from $stream;
    }

    /**
     * @template T
     * @param Closure(string $model): T $step
     * @return T
     */
    protected function withFallback(string $model, Closure $step): mixed
    {
        if ($this->model === null) {
            $this->quietly($this->onStep, $model);
        }

        try {
            return $step($model);
        } catch (Throwable $e) {
            if ($model !== $this->model || ! ModelHealth::isMissingModel($e, $model)) {
                throw $e;
            }

            $this->quietly($this->onMissingModel, $model);

            $fallback = ($this->fallbackModel)();

            if ($fallback === null || $fallback === $model) {
                throw $e;
            }

            try {
                return $step($fallback);
            } catch (Throwable $e) {
                if (ModelHealth::isMissingModel($e, $fallback)) {
                    $this->quietly($this->onMissingModel, $fallback);
                }

                throw $e;
            }
        }
    }

    /**
     * Keeping track of models is never worth failing a call for.
     */
    protected function quietly(Closure $callback, string $model): void
    {
        try {
            $callback($model);
        } catch (Throwable) {
        }
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
