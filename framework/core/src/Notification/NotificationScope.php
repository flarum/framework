<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Notification;

use Carbon\Carbon;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Queue;
use Illuminate\Support\Str;

/**
 * Limits the notifications one action (a reply, say) causes to one per user and
 * channel, whichever extensions send them, including from queued jobs.
 *
 * Claims are stored in the database because the jobs of one action may run in
 * different workers at the same time.
 */
class NotificationScope
{
    public const PAYLOAD_KEY = 'flarumNotificationScope';

    /**
     * Long enough for a failed job to be retried by hand.
     */
    public const RETENTION_DAYS = 7;

    protected const PRUNE_BATCH = 5000;

    protected static ?string $current = null;

    /**
     * The scope each running job replaced, by job.
     *
     * @var array<int, string|null>
     */
    protected static array $replaced = [];

    protected static bool $payloadHookAdded = false;

    public function __construct(
        protected ConnectionInterface $db
    ) {
    }

    /**
     * Run the callback in a scope of its own.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public static function run(callable $callback): mixed
    {
        $previous = static::$current;
        // Time-ordered, so new claims append to the primary key and old ones are pruned together.
        static::$current = (string) Str::orderedUuid();

        try {
            return $callback();
        } finally {
            static::$current = $previous;
        }
    }

    public static function current(): ?string
    {
        return static::$current;
    }

    /**
     * Claim the users for the blueprint on the channel, and return the IDs of
     * those it may notify: users nothing else in the scope has notified there.
     *
     * @param int[] $userIds
     * @return int[]
     */
    public function claim(string $scope, string $channel, BlueprintInterface $blueprint, array $userIds): array
    {
        $userIds = array_values(array_unique($userIds));

        if (empty($userIds)) {
            return [];
        }

        // The same type and subject, so a retried job wins its claims again.
        $claimedBy = $blueprint::getType().':'.$blueprint->getSubject()?->getKey();
        $now = Carbon::now();
        $inserted = 0;

        // In one order everywhere, so workers inserting overlapping rows don't deadlock.
        sort($userIds);

        foreach (array_chunk($userIds, 200) as $chunk) {
            $inserted += $this->db->table('notification_scope_claims')->insertOrIgnore(array_map(fn (int $userId) => [
                'scope' => $scope,
                'user_id' => $userId,
                'channel' => $channel,
                'claimed_by' => $claimedBy,
                'created_at' => $now,
            ], $chunk));
        }

        if (mt_rand(1, 100) <= 2) {
            static::prune($this->db);
        }

        if ($inserted === count($userIds)) {
            return $userIds;
        }

        $won = [];

        foreach (array_chunk($userIds, 200) as $chunk) {
            $won = array_merge($won, $this->db->table('notification_scope_claims')
                ->useWritePdo()
                ->where('scope', $scope)
                ->whereIn('user_id', $chunk)
                ->where('channel', $channel)
                ->where('claimed_by', $claimedBy)
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all());
        }

        return $won;
    }

    /**
     * Delete expired claims, a batch at a time so one large action's claims
     * don't all expire in a single statement.
     */
    public static function prune(ConnectionInterface $db): void
    {
        $db->table('notification_scope_claims')
            ->where('created_at', '<', Carbon::now()->subDays(static::RETENTION_DAYS))
            ->limit(static::PRUNE_BATCH)
            ->delete();
    }

    /**
     * @internal
     */
    public static function addToPayload(): array
    {
        return static::$current === null ? [] : [static::PAYLOAD_KEY => static::$current];
    }

    /**
     * @internal
     */
    public static function jobProcessing(JobProcessing $event): void
    {
        static::$replaced[spl_object_id($event->job)] = static::$current;

        static::$current = $event->job->payload()[static::PAYLOAD_KEY] ?? null;
    }

    /**
     * @internal
     */
    public static function jobFinished(JobProcessed|JobExceptionOccurred|JobFailed $event): void
    {
        $id = spl_object_id($event->job);

        if (array_key_exists($id, static::$replaced)) {
            static::$current = static::$replaced[$id];

            unset(static::$replaced[$id]);
        }
    }

    /**
     * @internal
     */
    public static function registerQueueHooks(Dispatcher $events): void
    {
        // Payload hooks are static and outlive the app, which tests boot many times.
        if (! static::$payloadHookAdded) {
            Queue::createPayloadUsing(fn () => static::addToPayload());
            static::$payloadHookAdded = true;
        }

        $events->listen(JobProcessing::class, [static::class, 'jobProcessing']);
        $events->listen([JobProcessed::class, JobExceptionOccurred::class, JobFailed::class], [static::class, 'jobFinished']);
    }
}
