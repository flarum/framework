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
use Flarum\Ai\Models\TemperatureSupport;
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
 * - A model that refuses the admin's temperature, as current Claude models
 *   do, is asked again without one, and from then on asked without one
 *   ({@see TemperatureSupport}).
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
        protected Closure $onStep,
        protected string $connection,
        protected TemperatureSupport $temperatures,
        protected string $feature
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
        return $this->withFallback($model, fn (string $model) => $this->withTemperature(
            $model,
            $options,
            fn (TextGenerationOptions $options) => $this->gateway->generateTextStep($provider, $model, $instructions, $messages, $tools, $schema, $options, $timeout, $stepContext)
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
        // Started here, so a request that fails fails before anything is sent on.
        $stream = $this->withFallback($model, function (string $model) use ($invocationId, $provider, $instructions, $messages, $tools, $schema, $options, $timeout, $stepContext): Generator {
            return $this->withTemperature($model, $options, function (TextGenerationOptions $options) use ($invocationId, $provider, $model, $instructions, $messages, $tools, $schema, $timeout, $stepContext): Generator {
                $stream = $this->gateway->generateStreamStep($invocationId, $provider, $model, $instructions, $messages, $tools, $schema, $options, $timeout, $stepContext);
                $stream->current();

                return $stream;
            });
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

    /**
     * Sends the step with its temperature, the admin's or the agent's, unless
     * the model is known to refuse one, and again without it if it refuses
     * it now.
     *
     * @template T
     * @param Closure(TextGenerationOptions $options): T $send
     * @return T
     */
    protected function withTemperature(string $model, ?TextGenerationOptions $options, Closure $send): mixed
    {
        $known = $this->temperatures->refused($this->feature, $this->connection, $model);
        $sent = $this->options($options, $known);

        try {
            return $send($sent);
        } catch (Throwable $e) {
            // Every provider that takes a temperature takes one up to 1: a
            // higher one refused may only be out of the model's range.
            if ($known || $sent->temperature === null || $sent->temperature > 1 || ! TemperatureSupport::isRefusal($e)) {
                throw $e;
            }

            $this->quietly(fn () => $this->temperatures->remember($this->feature, $this->connection, $model), $model);

            return $send($this->options($options, true));
        }
    }

    /**
     * @param bool $withoutTemperature For a model that refuses one: the agent's own too.
     */
    protected function options(?TextGenerationOptions $options, bool $withoutTemperature = false): TextGenerationOptions
    {
        $values = $options ? get_object_vars($options) : [];

        return new TextGenerationOptions(...[
            ...$values,
            'temperature' => $withoutTemperature ? null : $this->temperature ?? $values['temperature'] ?? null,
            'maxTokens' => $this->maxTokens ?? $values['maxTokens'] ?? null,
        ]);
    }
}
