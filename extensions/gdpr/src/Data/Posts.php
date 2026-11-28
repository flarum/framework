<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Data;

use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Deleting;
use Flarum\Notification\Notification;
use Flarum\Post\Post;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Support\Arr;
use RuntimeException;

class Posts extends Type
{
    /**
     * How many times the author's posts are deleted in one erasure.
     *
     * The discussions are rescanned before every pass, so a reply written to a
     * discussion an earlier scan did not have is caught by the next one. The
     * cap keeps someone who keeps posting from holding the erasure open; when
     * it runs out with posts still there, the erasure fails rather than
     * reporting success with posts left behind.
     */
    private const DELETE_PASSES = 10;

    /**
     * How many discussions are deleted and refreshed in one transaction.
     */
    private const DISCUSSIONS_PER_TRANSACTION = 100;

    public static function piiFields(): array
    {
        return ['ip_address'];
    }

    public function export(): ?array
    {
        $exportData = [];

        Post::query()
            ->where('user_id', $this->user->id)
            ->where('type', 'comment')
            ->where('is_private', false) // We don't export posts marked as private, extensions which handle the private flag must export as neccessary
            ->whereVisibleTo($this->user)
            ->orderBy('created_at', 'asc')
            ->each(function (Post $post) use (&$exportData) {
                $exportData[] = ["posts/post-{$post->id}.json" => $this->encodeForExport($this->sanitize($post))];
            });

        return $exportData;
    }

    protected function sanitize(Post $post): array
    {
        return Arr::only($post->toArray(), [
            'content', 'created_at',
            'ip_address', 'discussion_id',
        ]);
    }

    public function anonymize(): void
    {
        Post::query()
            ->where('user_id', $this->user->id)
            ->update(['ip_address' => null]);
    }

    public function delete(): void
    {
        $db = (new Post())->getConnection();

        // The discussions are scanned before each pass, so a reply written to
        // a discussion outside the previous scan is caught by the next pass.
        // The deletion is by author, as it was before the posts were handed to
        // the event as a fixed set.
        for ($pass = 0; $pass < self::DELETE_PASSES; $pass++) {
            $discussionIds = Post::query()
                ->where('user_id', $this->user->id)
                ->orderBy('discussion_id')
                ->distinct()
                ->pluck('discussion_id')
                ->all();

            if ($discussionIds === []) {
                return;
            }

            foreach (array_chunk($discussionIds, self::DISCUSSIONS_PER_TRANSACTION) as $ids) {
                $this->deleteChunk($db, array_map('intval', $ids));
            }
        }

        // A pass that deleted nothing is rescanned rather than taken as the
        // end: the post it was about can be gone while a reply written
        // meanwhile is still there. Running out of passes is a failure, not a
        // success with the author's posts left behind.
        if (Post::query()->where('user_id', $this->user->id)->exists()) {
            throw new RuntimeException(sprintf(
                'Posts of user %d are still present after %d deletion passes; the erasure will be retried.',
                $this->user->id,
                self::DELETE_PASSES
            ));
        }
    }

    /**
     * Delete the author's posts in the given discussions and refresh them.
     *
     * A chunk is one transaction: if a discussion in it cannot be refreshed,
     * that chunk's posts are still there for the retry, rather than leaving
     * stale counts and pointers, or a discussion behind empty. Its locks are
     * released as soon as it is done instead of at the end of the erasure, so
     * a reply to a discussion the erasure has not reached yet waits for at
     * most one chunk.
     *
     * @param list<int> $discussionIds
     */
    protected function deleteChunk(Connection $db, array $discussionIds): void
    {
        // Domain events raised by a discussion this chunk empties wait for the
        // commit: a later refresh in the same chunk can fail and roll the whole
        // chunk back, and an event that escaped first would describe rows that
        // are back, then be dispatched again by the retry.
        $snapshotMayPrecedeLock = $db->transactionLevel() > 0;

        $db->transaction(function () use ($db, $discussionIds, $snapshotMayPrecedeLock) {
            $refreshed = [];

            $deleted = Post::deleteWithEvents(
                Post::query()
                    ->where('user_id', $this->user->id)
                    ->whereIn('discussion_id', $discussionIds),
                function ($targets) use (&$refreshed) {
                    $refreshed = (clone $targets)->select('posts.discussion_id')->distinct()->pluck('discussion_id')->all();

                    Notification::query()
                        ->whereSubjectModel(Post::class)
                        ->whereIn('subject_id', $targets)
                        ->delete();

                    return $targets->delete();
                },
                [$this->user->id],
                null,
                $discussionIds,
                $snapshotMayPrecedeLock,
            );

            if ($deleted) {
                $events = $this->refreshDiscussions($refreshed);

                // The events of a discussion this chunk empties wait for the
                // commit: a later refresh can roll the whole chunk back.
                if ($events !== []) {
                    $db->afterCommit(static function () use ($events) {
                        $dispatcher = resolve(Dispatcher::class);

                        foreach ($events as $event) {
                            // Each event carries the tags loaded before the
                            // earlier events were dispatched, and their listeners
                            // have updated those tags since.
                            $discussion = $event->discussion ?? null;

                            if ($discussion instanceof Discussion && $discussion->relationLoaded('tags')) {
                                $discussion->tags->each->refresh();
                            }

                            $dispatcher->dispatch($event);
                        }
                    });
                }
            }
        }, Post::conflictAttempts($db));
    }

    /**
     * @param list<int> $discussionIds
     * @return list<object> The domain events to dispatch once the chunk commits.
     */
    protected function refreshDiscussions(array $discussionIds): array
    {
        $discussions = Discussion::query()->whereIn('id', $discussionIds)->orderBy('id')->get();

        // The chunk's first and last posts are looked up together: one query
        // per discussion would be an N+1.
        $postIds = $discussions
            ->flatMap(fn (Discussion $discussion) => [$discussion->first_post_id, $discussion->last_post_id])
            ->filter()
            ->unique()
            ->values()
            ->all();

        $existingPostIds = $postIds
            ? array_flip(array_map('intval', Post::query()->whereIn('id', $postIds)->pluck('id')->all()))
            : [];

        $events = [];

        foreach ($discussions as $discussion) {
            $events = array_merge($events, $this->refreshDiscussion($discussion, $existingPostIds));
        }

        return $events;
    }

    /**
     * @param array<int, int> $existingPostIds The chunk's first and last post
     *                                         ids that still exist, as keys.
     * @return list<object> The domain events to dispatch once the chunk commits.
     */
    protected function refreshDiscussion(Discussion $discussion, array $existingPostIds): array
    {
        // As when the last post of a discussion is deleted.
        if (! $discussion->posts()->exists()) {
            return $this->deleteDiscussion($discussion);
        }

        // Their opening post is gone, so start at the first post that is
        // left. Only the post: the discussion is still the one they started.
        if (! isset($existingPostIds[$discussion->first_post_id])) {
            $discussion->first_post_id = $discussion->posts()->where('type', 'comment')->oldest('number')->value('id');
        }

        if (! isset($existingPostIds[$discussion->last_post_id])) {
            $discussion->refreshLastPost();
        }

        $discussion->refreshCommentCount();
        $discussion->refreshParticipantCount();
        $discussion->save();

        return [];
    }

    /**
     * @return list<object> The domain events to dispatch once the chunk commits.
     */
    protected function deleteDiscussion(Discussion $discussion): array
    {
        $actor = $this->erasureRequest->processedBy ?? $this->user;

        // The "before" event is part of the deletion; the events the deletion
        // raises are dispatched after the commit.
        resolve(Dispatcher::class)->dispatch(new Deleting($discussion, $actor, []));

        $discussion->delete();

        $events = [];

        foreach ($discussion->releaseEvents() as $event) {
            if (! is_object($event)) {
                continue;
            }

            if (property_exists($event, 'actor') && ! $event->actor) {
                $event->actor = $actor;
            }

            $events[] = $event;
        }

        return $events;
    }
}
