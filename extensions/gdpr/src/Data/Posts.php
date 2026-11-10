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
        $posts = Post::query()->where('user_id', $this->user->id);

        // The posts are deleted in one query, which skips everything that
        // normally happens when a post is deleted. So note which discussions
        // they were in, and afterwards leave each one as if its posts had
        // been deleted one at a time.
        $discussionIds = (clone $posts)->distinct()->pluck('discussion_id')->all();

        Notification::query()
            ->whereSubjectModel(Post::class)
            ->whereIn('subject_id', (clone $posts)->select('id')->toBase())
            ->delete();

        $posts->delete();

        foreach (array_chunk($discussionIds, 100) as $ids) {
            Discussion::query()->whereIn('id', $ids)->get()->each($this->refreshDiscussion(...));
        }
    }

    protected function refreshDiscussion(Discussion $discussion): void
    {
        // As when the last post of a discussion is deleted.
        if (! $discussion->posts()->exists()) {
            $this->deleteDiscussion($discussion);

            return;
        }

        // Their opening post is gone, so start at the first post that is
        // left. Only the post: the discussion is still the one they started.
        if (! $discussion->first_post_id || ! $discussion->firstPost()->exists()) {
            $discussion->first_post_id = $discussion->posts()->where('type', 'comment')->oldest('number')->value('id');
        }

        if (! $discussion->last_post_id || ! $discussion->lastPost()->exists()) {
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
