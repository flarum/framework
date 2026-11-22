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
use Illuminate\Support\Arr;

class Posts extends Type
{
    /**
     * How many times the author's posts are deleted in one erasure.
     *
     * The posts are fixed when the discussions are scanned, so deleting by
     * author again catches a reply written to a discussion the scan missed.
     * The cap keeps someone who keeps posting from holding the erasure open.
     */
    private const DELETE_PASSES = 10;

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
        $db = Post::query()->getConnection();

        // The deletion and the refreshes commit together: if a discussion
        // cannot be refreshed, its posts are still there for the retry, rather
        // than leaving stale counts and pointers, or a discussion behind empty.
        $db->transaction(function () {
            // The discussions are scanned before each deletion, so a reply
            // written to a discussion outside the previous scan is caught by
            // the next pass. The deletion is by author, as it was before the
            // posts were handed to the event as a fixed set.
            for ($pass = 0; $pass < self::DELETE_PASSES; $pass++) {
                $discussionIds = [];

                $deleted = Post::deleteWithEvents(
                    Post::query()->where('user_id', $this->user->id),
                    function ($targets) use (&$discussionIds) {
                        $discussionIds = (clone $targets)->select('posts.discussion_id')->distinct()->pluck('discussion_id')->all();

                        Notification::query()
                            ->whereSubjectModel(Post::class)
                            ->whereIn('subject_id', $targets)
                            ->delete();

                        return $targets->delete();
                    },
                    [$this->user->id]
                );

                foreach (array_chunk($discussionIds, 100) as $ids) {
                    $discussions = Discussion::query()->whereIn('id', $ids)->get();

                    // The chunk's first and last posts are looked up together:
                    // one query per discussion would be an N+1.
                    $postIds = $discussions
                        ->flatMap(fn (Discussion $discussion) => [$discussion->first_post_id, $discussion->last_post_id])
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();

                    $existingPostIds = $postIds
                        ? array_flip(array_map('intval', Post::query()->whereIn('id', $postIds)->pluck('id')->all()))
                        : [];

                    $discussions->each(fn (Discussion $discussion) => $this->refreshDiscussion($discussion, $existingPostIds));
                }

                if (! $deleted) {
                    return;
                }
            }
        }, Post::conflictAttempts($db));
    }

    /**
     * @param array<int, int> $existingPostIds The chunk's first and last post
     *                                         ids that still exist, as keys.
     */
    protected function refreshDiscussion(Discussion $discussion, array $existingPostIds): void
    {
        // As when the last post of a discussion is deleted.
        if (! $discussion->posts()->exists()) {
            $this->deleteDiscussion($discussion);

            return;
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
    }

    protected function deleteDiscussion(Discussion $discussion): void
    {
        $events = resolve(Dispatcher::class);
        $actor = $this->erasureRequest->processedBy ?? $this->user;

        $events->dispatch(new Deleting($discussion, $actor, []));

        $discussion->delete();

        foreach ($discussion->releaseEvents() as $event) {
            if (property_exists($event, 'actor') && ! $event->actor) {
                $event->actor = $actor;
            }

            $events->dispatch($event);
        }
    }
}
