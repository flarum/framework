<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Queue;

use Illuminate\Contracts\Queue\Factory as Queue;
use Illuminate\Queue\Events\JobRetryRequested;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Illuminate\Support\Str;

/**
 * Lists, retries and forgets failed jobs for the admin dashboard.
 *
 * Retrying mirrors Illuminate\Queue\Console\RetryCommand exactly (reset
 * attempts, refresh retryUntil, re-push raw, then forget) so the API and the
 * queue:retry CLI command behave identically. Everything is driven by the
 * queue.failer provider, so this works for any backend whose failer core
 * knows about.
 */
class FailedJobs
{
    public function __construct(
        protected FailedJobProviderInterface $failer,
        protected Queue $queue,
        protected \Illuminate\Contracts\Events\Dispatcher $events
    ) {
    }

    /**
     * @return array<int, array{id: string, connection: ?string, queue: string, name: string, failed_at: string, exception: string}>
     */
    public function all(): array
    {
        return array_map(function ($job) {
            $payload = json_decode($job->payload, true);

            return [
                'id' => $job->id,
                'connection' => $job->connection,
                'queue' => $job->queue,
                'name' => $payload['displayName'] ?? ($payload['job'] ?? 'unknown'),
                'failed_at' => $job->failed_at,
                'exception' => $job->exception,
            ];
        }, $this->failer->all());
    }

    /**
     * Re-dispatch a failed job and remove its failed record. Returns false if
     * no such job exists.
     */
    public function retry(string $id): bool
    {
        $job = $this->failer->find($id);

        if (is_null($job)) {
            return false;
        }

        $this->events->dispatch(new JobRetryRequested($job));

        $this->queue->connection($job->connection)->pushRaw(
            $this->refreshRetryUntil($this->resetAttempts($job->payload)),
            $job->queue
        );

        $this->failer->forget($id);

        return true;
    }

    public function retryAll(): void
    {
        foreach ($this->failer->ids() as $id) {
            $this->retry(is_array($id) ? $id['id'] : $id);
        }
    }

    public function forget(string $id): bool
    {
        return (bool) $this->failer->forget($id);
    }

    protected function resetAttempts(string $payload): string
    {
        $payload = json_decode($payload, true);

        if (isset($payload['attempts'])) {
            $payload['attempts'] = 0;
        }

        return json_encode($payload);
    }

    protected function refreshRetryUntil(string $payload): string
    {
        $decoded = json_decode($payload, true);

        if (! isset($decoded['data']['command'])) {
            return $payload;
        }

        $instance = $this->instanceFromCommand($decoded['data']['command']);

        if (is_object($instance) && ! $instance instanceof \__PHP_Incomplete_Class && method_exists($instance, 'retryUntil')) {
            $retryUntil = $instance->retryUntil();

            $decoded['retryUntil'] = $retryUntil instanceof \DateTimeInterface
                ? $retryUntil->getTimestamp()
                : $retryUntil;

            return json_encode($decoded);
        }

        return $payload;
    }

    protected function instanceFromCommand(string $command): mixed
    {
        if (Str::startsWith($command, 'O:')) {
            return @unserialize($command);
        }

        return null;
    }
}
