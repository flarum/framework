<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Connections;

use Aws\Exception\AwsException;
use Closure;
use Flarum\Ai\AiConfig;
use Flarum\Ai\AiManager;
use Flarum\Ai\ConnectionKeys;
use Flarum\Ai\Exception\LimitReachedException;
use Flarum\Ai\Exception\NotConfiguredException;
use Flarum\Ai\Http\ReplyEvents;
use Flarum\Ai\Models\ModelHealth;
use Illuminate\Http\Client\RequestException;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
use Laravel\Ai\Exceptions\ProviderConnectionException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Throwable;

/**
 * Sends a connection a short prompt and streams back what happens: its reply
 * as it's written, then the model that answered, how long it took and the
 * tokens it used, or what went wrong, in words the admin page can explain.
 *
 * A test is a real call: it's counted on the Usage tab, not for any feature,
 * and stops at the connection's monthly limit like any other.
 *
 * @internal
 */
class ConnectionTester
{
    /**
     * The longest message from a provider passed on to the admin page.
     */
    protected const MESSAGE_MAX_LENGTH = 300;

    public function __construct(
        protected AiManager $manager,
        protected AiConfig $config,
        protected ConnectionKeys $keys
    ) {
    }

    /**
     * @param array<string, mixed>|null $candidate A connection that isn't saved yet, with its key, to test in place of the saved one.
     * @param Closure(string $type, array<string, mixed> $data=): void $send
     */
    public function run(string $name, ?array $candidate, Closure $send): void
    {
        $send('start');

        $test = fn () => $this->test($name, $candidate, $send);

        $candidate === null ? $test() : $this->manager->withCandidate($name, $candidate, $test);
    }

    /**
     * @param array<string, mixed>|null $candidate
     * @param Closure(string $type, array<string, mixed> $data=): void $send
     */
    protected function test(string $name, ?array $candidate, Closure $send): void
    {
        $connection = $candidate ?? $this->config->connections()[$name];
        $model = is_string($connection['model'] ?? null) && $connection['model'] !== '' ? $connection['model'] : null;
        $start = hrtime(true);
        $firstText = null;

        try {
            $model ??= $this->manager->textProvider($name)->defaultTextModel();

            $reply = (new TestAgent())->stream(TestAgent::PROMPT, [], $name, $model);

            $usage = ReplyEvents::relay($reply, function (string $type, array $data = []) use ($send, $start, &$firstText) {
                $firstText ??= intdiv(hrtime(true) - $start, 1_000_000);
                $send($type, $data);
            });
        } catch (Throwable $e) {
            $send('error', $this->describe($e, $model, $this->key($name, $connection)) + ['model' => $model]);

            return;
        }

        $send('done', [
            'model' => $model,
            'firstTextMs' => $firstText,
            'inputTokens' => $usage->inputTokens,
            'outputTokens' => $usage->outputTokens,
        ]);
    }

    /**
     * The key the call was made with, kept out of what the provider says back.
     *
     * @param array<string, mixed> $connection
     */
    protected function key(string $name, array $connection): ?string
    {
        if (is_string($connection['key'] ?? null)) {
            return $connection['key'];
        }

        // A saved connection's key is in its own table, not its settings.
        return $this->config->isAdminConnection($name) ? $this->keys->get($name) : null;
    }

    /**
     * What went wrong, as a code the admin page has words for, and what the
     * provider said, if anything.
     *
     * @return array<string, mixed>
     */
    protected function describe(Throwable $e, ?string $model, ?string $key): array
    {
        if ($e instanceof LimitReachedException) {
            return ['code' => 'limit', 'tokens' => $e->tokens, 'resetsAt' => $e->resetsAt->toIso8601String()];
        }

        if ($e instanceof NotConfiguredException) {
            return ['code' => 'not_configured'];
        }

        if ($model !== null && ModelHealth::isMissingModel($e, $model)) {
            return ['code' => 'model'];
        }

        $code = match (true) {
            $e instanceof ProviderConnectionException => 'unreachable',
            $e instanceof RateLimitedException => 'rate_limited',
            $e instanceof InsufficientCreditsException => 'credits',
            $e instanceof ProviderOverloadedException => 'overloaded',
            default => 'failed',
        };

        $result = ['code' => $code];

        for ($cause = $e; $cause !== null; $cause = $cause->getPrevious()) {
            if ($cause instanceof RequestException) {
                $status = $cause->response->status();
                $result['status'] = $status;

                if ($code === 'failed' && in_array($status, [401, 403], true)) {
                    $result['code'] = 'auth';
                }

                $message = $cause->response->json('error.message') ?? $cause->response->json('message') ?? $cause->response->json('error');
                $message = is_string($message) ? $message : $cause->response->body();

                return $result + $this->message($message, $key);
            }

            if (class_exists(AwsException::class) && $cause instanceof AwsException) {
                if ($code === 'failed' && in_array($cause->getAwsErrorCode(), ['AccessDeniedException', 'UnrecognizedClientException'], true)) {
                    $result['code'] = 'auth';
                }

                return $result + $this->message($cause->getAwsErrorMessage() ?? $cause->getMessage(), $key);
            }
        }

        return $result + $this->message($e->getMessage(), $key);
    }

    /**
     * @return array<string, string>
     */
    protected function message(string $message, ?string $key): array
    {
        // Some providers repeat the key they were given when refusing it.
        if ($key !== null && $key !== '') {
            $message = str_replace($key, '…', $message);
        }

        $message = trim((string) preg_replace('/\s+/', ' ', strip_tags($message)));

        if ($message === '') {
            return [];
        }

        return ['message' => mb_strlen($message) > self::MESSAGE_MAX_LENGTH ? mb_substr($message, 0, self::MESSAGE_MAX_LENGTH).'…' : $message];
    }
}
